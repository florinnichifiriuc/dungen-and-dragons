<?php

namespace App\Services;

use App\Models\AiRequest;
use App\Models\CampaignSession;
use App\Models\SessionNote;
use App\Models\SessionAttendance;
use App\Models\SessionReward;
use App\Models\User;
use App\Policies\CampaignPolicy;
use App\Services\ConditionTimerAcknowledgementService;
use App\Services\ConditionTimerChronicleService;
use App\Services\ConditionTimerSummaryProjector;
use App\Services\ConditionTimerSummaryShareService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SessionExportService
{
    public function __construct(
        private readonly CampaignPolicy $campaignPolicy,
        private readonly ConditionTimerChronicleService $conditionTimerChronicle,
        private readonly ConditionTimerSummaryProjector $conditionTimerSummaryProjector,
        private readonly ConditionTimerAcknowledgementService $conditionTimerAcknowledgements,
        private readonly ConditionTimerSummaryShareService $conditionTimerSummaryShares
    )
    {
    }

    public function buildExportData(CampaignSession $session, User $viewer): array
    {
        $session->loadMissing([
            'campaign.group.memberships',
            'campaign.roleAssignments',
            'creator:id,name',
            'turn:id,number,window_started_at,processed_at',
            'notes.author:id,name',
            'diceRolls.roller:id,name',
            'initiativeEntries',
            'attendances.user:id,name',
            'recaps.author:id,name',
            'rewards.recorder:id,name',
            'aiRequests' => fn ($query) => $query
                ->where('request_type', 'npc_dialogue')
                ->latest()
                ->limit(20),
        ]);

        $canManage = $this->campaignPolicy->update($viewer, $session->campaign);

        $notes = $session->notes
            ->when(! $canManage, function ($collection) {
                return $collection->reject(fn (SessionNote $note) => $note->visibility === SessionNote::VISIBILITY_GM);
            })
            ->sortByDesc('created_at')
            ->sortByDesc('is_pinned')
            ->values()
            ->map(fn (SessionNote $note) => [
                'id' => $note->id,
                'content' => $note->content,
                'visibility' => $note->visibility,
                'is_pinned' => $note->is_pinned,
                'author' => [
                    'id' => $note->author->id,
                    'name' => $note->author->name,
                ],
                'created_at' => $note->created_at?->clone()->setTimezone('UTC'),
            ]);

        $diceRolls = $session->diceRolls
            ->sortByDesc('created_at')
            ->values()
            ->map(fn ($roll) => [
                'id' => $roll->id,
                'expression' => $roll->expression,
                'result_total' => $roll->result_total,
                'result_breakdown' => $roll->result_breakdown,
                'roller' => [
                    'id' => $roll->roller->id,
                    'name' => $roll->roller->name,
                ],
                'created_at' => $roll->created_at?->clone()->setTimezone('UTC'),
            ]);

        $initiative = $session->initiativeEntries
            ->sortBy('order_index')
            ->values()
            ->map(fn ($entry) => [
                'id' => $entry->id,
                'name' => $entry->name,
                'dexterity_mod' => $entry->dexterity_mod,
                'initiative' => $entry->initiative,
                'is_current' => $entry->is_current,
                'order_index' => $entry->order_index,
            ]);

        $aiDialogues = $session->aiRequests
            ->sortByDesc('created_at')
            ->values()
            ->map(fn (AiRequest $request) => [
                'id' => $request->id,
                'npc_name' => $request->meta['npc_name'] ?? null,
                'tone' => $request->meta['tone'] ?? null,
                'prompt' => $request->prompt,
                'reply' => $request->response_text,
                'status' => $request->status,
                'created_at' => $request->created_at?->clone()->setTimezone('UTC'),
            ]);

        $attendanceResponses = $session->attendances
            ->sortByDesc(fn (SessionAttendance $attendance) => $attendance->responded_at ?? $attendance->updated_at ?? $attendance->created_at)
            ->values()
            ->map(fn (SessionAttendance $attendance) => [
                'id' => $attendance->id,
                'status' => $attendance->status,
                'note' => $attendance->note,
                'responded_at' => $attendance->responded_at?->clone()->setTimezone('UTC'),
                'user' => [
                    'id' => $attendance->user->id,
                    'name' => $attendance->user->name,
                ],
            ]);

        $attendanceCounts = [
            'yes' => $session->attendances->where('status', SessionAttendance::STATUS_YES)->count(),
            'maybe' => $session->attendances->where('status', SessionAttendance::STATUS_MAYBE)->count(),
            'no' => $session->attendances->where('status', SessionAttendance::STATUS_NO)->count(),
        ];

        $recaps = $session->recaps
            ->sortByDesc('created_at')
            ->values()
            ->map(fn ($recap) => [
                'id' => $recap->id,
                'title' => $recap->title,
                'body' => $recap->body,
                'author' => [
                    'id' => $recap->author->id,
                    'name' => $recap->author->name,
                ],
                'created_at' => $recap->created_at?->clone()->setTimezone('UTC'),
            ]);

        $rewards = $session->rewards
            ->sortByDesc('created_at')
            ->values()
            ->map(fn (SessionReward $reward) => [
                'id' => $reward->id,
                'reward_type' => $reward->reward_type,
                'title' => $reward->title,
                'quantity' => $reward->quantity,
                'awarded_to' => $reward->awarded_to,
                'notes' => $reward->notes,
                'recorded_at' => $reward->created_at?->clone()->setTimezone('UTC'),
                'recorder' => [
                    'id' => $reward->recorder->id,
                    'name' => $reward->recorder->name,
                ],
            ]);

        $storedRecordingUrl = null;
        $storedRecordingName = null;

        if ($session->hasStoredRecording()) {
            $storedRecordingUrl = Storage::disk($session->recording_disk)->url($session->recording_path);
            $storedRecordingName = basename($session->recording_path);
        }

        $group = $session->campaign->group;

        $conditionChronicle = $group
            ? $this->conditionTimerChronicle->exportChronicle($group, $canManage)
            : [];

        $conditionSummary = null;
        $conditionShare = null;

        if ($group) {
            $summary = $this->conditionTimerSummaryProjector->projectForGroup($group);
            $summary = $this->conditionTimerAcknowledgements->hydrateSummaryForUser(
                $summary,
                $group,
                $viewer,
                $canManage,
            );
            $summary = $this->conditionTimerChronicle->hydrateSummaryForUser(
                $summary,
                $group,
                $viewer,
                $canManage,
            );

            $conditionSummary = $summary;

            $shareRecord = $this->conditionTimerSummaryShares->activeShareForGroup($group);

            if ($shareRecord) {
                $conditionShare = $this->conditionTimerSummaryShares->presentShareForExport($shareRecord);
            }
        }

        return [
            'metadata' => [
                'campaign' => [
                    'id' => $session->campaign->id,
                    'title' => $session->campaign->title,
                ],
                'session' => [
                    'id' => $session->id,
                    'title' => $session->title,
                    'agenda' => $session->agenda,
                    'summary' => $session->summary,
                    'session_date' => $session->session_date?->clone()->setTimezone('UTC'),
                    'duration_minutes' => $session->duration_minutes,
                    'location' => $session->location,
                    'recording_url' => $session->recording_url,
                    'stored_recording_url' => $storedRecordingUrl,
                    'stored_recording_name' => $storedRecordingName,
                    'turn' => $session->turn ? [
                        'id' => $session->turn->id,
                        'number' => $session->turn->number,
                        'window_started_at' => $session->turn->window_started_at?->clone()->setTimezone('UTC'),
                        'processed_at' => $session->turn->processed_at?->clone()->setTimezone('UTC'),
                    ] : null,
                    'creator' => [
                        'id' => $session->creator->id,
                        'name' => $session->creator->name,
                    ],
                ],
                'generated_at' => now('UTC'),
                'viewer' => [
                    'id' => $viewer->id,
                    'name' => $viewer->name,
                    'can_manage' => $canManage,
                ],
            ],
            'notes' => $notes,
            'dice_rolls' => $diceRolls,
            'initiative' => $initiative,
            'ai_dialogues' => $aiDialogues,
            'attendance' => [
                'responses' => $attendanceResponses,
                'counts' => $attendanceCounts,
            ],
            'recaps' => $recaps,
            'rewards' => $rewards,
            'condition_timer_chronicle' => $conditionChronicle,
            'condition_timer_summary' => $conditionSummary,
            'condition_timer_summary_share' => $conditionShare,
        ];
    }

    public function generateMarkdown(array $data): string
    {
        $metadata = $data['metadata'];
        $session = $metadata['session'];
        $campaign = $metadata['campaign'];

        $lines = [];
        $lines[] = '# Session Chronicle: ' . $session['title'];
        $lines[] = '';
        $lines[] = '- Campaign: ' . $campaign['title'];
        $lines[] = '- Compiled at: ' . $metadata['generated_at']->format('Y-m-d H:i \U\T\C');

        if ($session['session_date'] instanceof Carbon) {
            $lines[] = '- Session date: ' . $session['session_date']->format('Y-m-d H:i \U\T\C');
        }

        if ($session['duration_minutes']) {
            $lines[] = '- Duration: ' . $session['duration_minutes'] . ' minutes';
        }

        if ($session['location']) {
            $lines[] = '- Location: ' . $session['location'];
        }

        if ($session['turn']) {
            $turn = $session['turn'];
            $lines[] = '- Linked turn: #' . $turn['number'];
        }

        if ($session['recording_url']) {
            $lines[] = '- External recording: ' . $session['recording_url'];
        }

        if ($session['stored_recording_url']) {
            $lines[] = '- Vault recording: ' . $session['stored_recording_url'];
        }

        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';

        if ($session['agenda']) {
            $lines[] = '## Agenda';
            $lines[] = $session['agenda'];
            $lines[] = '';
        }

        if ($session['summary']) {
            $lines[] = '## Summary';
            $lines[] = $session['summary'];
            $lines[] = '';
        }

        $conditionSummary = $data['condition_timer_summary'] ?? null;
        $conditionShare = $data['condition_timer_summary_share'] ?? null;

        if ($conditionSummary && count($conditionSummary['entries'] ?? []) > 0) {
            $lines[] = '## Active Condition Outlook';
            if ($conditionShare && ! empty($conditionShare['url'])) {
                $lines[] = '- Shareable view: ' . $conditionShare['url'];

                if (! empty($conditionShare['expires_at'])) {
                    $lines[] = '- Share expires: ' . $this->formatCarbonTimestamp($conditionShare['expires_at']);
                }

                $shareStats = $conditionShare['stats'] ?? null;

                if ($shareStats) {
                    $lines[] = '- Total opens: ' . ($shareStats['total_views'] ?? 0);

                    if (! empty($shareStats['last_accessed_at'])) {
                        $lines[] = '- Last opened: ' . $this->formatCarbonTimestamp($shareStats['last_accessed_at']);
                    }

                    if (! empty($shareStats['recent_accesses'])) {
                        $lines[] = '- Recent guests:';

                        foreach ($shareStats['recent_accesses'] as $access) {
                            $timestamp = $this->formatCarbonTimestamp($access['accessed_at'] ?? null);
                            $details = array_filter([
                                $access['ip_address'] ?? null,
                                $access['user_agent'] ?? null,
                            ]);

                            $detailLine = $details ? ' (' . implode(' • ', $details) . ')' : '';
                            $lines[] = sprintf('  - %s%s', $timestamp, $detailLine);
                        }
                    }
                }

                $lines[] = '';
            }

            foreach ($conditionSummary['entries'] as $entry) {
                $tokenLabel = $entry['token']['label'] ?? 'Unknown presence';
                $mapTitle = $entry['map']['title'] ?? 'Unknown map';
                $lines[] = sprintf('### %s — %s', $tokenLabel, $mapTitle);

                foreach ($entry['conditions'] as $condition) {
                    $conditionLabel = $condition['label'] ?? Str::headline($condition['key'] ?? 'condition');
                    $roundsDisplay = $this->formatConditionRounds($condition);
                    $lines[] = sprintf('- %s — %s', $conditionLabel, $roundsDisplay);
                    $lines[] = '  - ' . ($condition['summary'] ?? 'No narrative summary available.');

                    if (($condition['acknowledged_by_viewer'] ?? false) === true) {
                        $lines[] = '  - Acknowledged by you.';
                    }

                    if (($metadata['viewer']['can_manage'] ?? false)
                        && array_key_exists('acknowledged_count', $condition)
                        && $condition['acknowledged_count'] !== null
                    ) {
                        $count = (int) $condition['acknowledged_count'];
                        $lines[] = sprintf(
                            '  - Acknowledged by %s.',
                            $count === 1 ? '1 party member' : sprintf('%d party members', $count),
                        );
                    }

                    $timeline = $condition['timeline'] ?? [];

                    if ($timeline !== []) {
                        $lines[] = '  - Timeline:';

                        foreach ($timeline as $event) {
                            $timestamp = $this->formatIsoTimestamp($event['recorded_at'] ?? null);
                            $summaryText = $event['summary'] ?? 'Adjustment';
                            $lines[] = sprintf('    - [%s] %s', $timestamp, $summaryText);

                            $detail = $event['detail']['summary'] ?? null;

                            if ($detail) {
                                $lines[] = '      - ' . $detail;
                            }
                        }
                    }
                }

                $lines[] = '';
            }
        } elseif ($conditionShare && ! empty($conditionShare['url'])) {
            $lines[] = '## Active Condition Outlook';
            $lines[] = 'No active condition timers at this time.';
            $lines[] = '';
            $lines[] = '- Shareable view: ' . $conditionShare['url'];

            if (! empty($conditionShare['expires_at'])) {
                $lines[] = '- Share expires: ' . $this->formatCarbonTimestamp($conditionShare['expires_at']);
            }

            $shareStats = $conditionShare['stats'] ?? null;

            if ($shareStats) {
                $lines[] = '- Total opens: ' . ($shareStats['total_views'] ?? 0);

                if (! empty($shareStats['last_accessed_at'])) {
                    $lines[] = '- Last opened: ' . $this->formatCarbonTimestamp($shareStats['last_accessed_at']);
                }

                if (! empty($shareStats['recent_accesses'])) {
                    $lines[] = '- Recent guests:';

                    foreach ($shareStats['recent_accesses'] as $access) {
                        $timestamp = $this->formatCarbonTimestamp($access['accessed_at'] ?? null);
                        $details = array_filter([
                            $access['ip_address'] ?? null,
                            $access['user_agent'] ?? null,
                        ]);

                        $detailLine = $details ? ' (' . implode(' • ', $details) . ')' : '';
                        $lines[] = sprintf('  - %s%s', $timestamp, $detailLine);
                    }
                }
            }

            $lines[] = '';
        }

        if (count($data['condition_timer_chronicle']) > 0) {
            $lines[] = '## Condition Timer Chronicle';

            foreach ($data['condition_timer_chronicle'] as $entry) {
                $timestamp = $entry['recorded_at'] instanceof Carbon
                    ? $entry['recorded_at']->format('Y-m-d H:i \U\T\C')
                    : 'Unknown time';

                $tokenLabel = $entry['token']['label'] ?? 'Unknown presence';
                $conditionLabel = Str::headline($entry['condition_key']);
                $lines[] = sprintf('- %s — %s • %s', $timestamp, $tokenLabel, $conditionLabel);
                $lines[] = '  - ' . $entry['summary'];

                if (! empty($entry['actor'])) {
                    $actor = $entry['actor'];
                    $role = $actor['role'] ? ' ('.$actor['role'].')' : '';
                    $lines[] = sprintf('  - Recorder: %s%s', $actor['name'], $role);
                }

                if ($entry['previous_rounds'] !== null || $entry['new_rounds'] !== null) {
                    $lines[] = sprintf(
                        '  - Rounds: %s → %s',
                        $entry['previous_rounds'] ?? '—',
                        $entry['new_rounds'] ?? '—',
                    );
                }
            }

            $lines[] = '';
        }

        if (count($data['rewards']) > 0) {
            $lines[] = '## Rewards & Loot Ledger';

            foreach ($data['rewards'] as $reward) {
                $timestamp = $reward['recorded_at'] instanceof Carbon
                    ? $reward['recorded_at']->format('Y-m-d H:i \U\T\C')
                    : 'Unknown time';
                $typeLabel = Str::headline($reward['reward_type']);
                $quantity = $reward['quantity'] ? ' x' . $reward['quantity'] : '';
                $recipient = filled($reward['awarded_to']) ? ' → ' . $reward['awarded_to'] : '';

                $lines[] = sprintf(
                    '- %s%s%s — %s (%s by %s)',
                    $reward['title'],
                    $quantity,
                    $recipient,
                    $typeLabel,
                    $timestamp,
                    $reward['recorder']['name'],
                );

                if (filled($reward['notes'])) {
                    $lines[] = '  - Notes: ' . $reward['notes'];
                }
            }

            $lines[] = '';
        }

        if (count($data['attendance']['responses']) > 0) {
            $lines[] = '## Attendance';
            $lines[] = sprintf('- Joining: %d', $data['attendance']['counts']['yes']);
            $lines[] = sprintf('- Tentative: %d', $data['attendance']['counts']['maybe']);
            $lines[] = sprintf('- Unavailable: %d', $data['attendance']['counts']['no']);
            $lines[] = '';
            $lines[] = '### Responses';

            foreach ($data['attendance']['responses'] as $response) {
                $timestamp = $response['responded_at'] instanceof Carbon
                    ? $response['responded_at']->format('Y-m-d H:i \U\T\C')
                    : 'Unknown time';

                $note = filled($response['note']) ? ' – ' . $response['note'] : '';

                $lines[] = sprintf(
                    '- %s: %s%s (%s)',
                    $response['user']['name'],
                    match ($response['status']) {
                        SessionAttendance::STATUS_YES => 'Joining',
                        SessionAttendance::STATUS_MAYBE => 'Tentative',
                        SessionAttendance::STATUS_NO => 'Unavailable',
                        default => ucfirst($response['status']),
                    },
                    $note,
                    $timestamp,
                );
            }

            $lines[] = '';
        }

        if (count($data['recaps']) > 0) {
            $lines[] = '## Session Recaps';
            foreach ($data['recaps'] as $recap) {
                $timestamp = $recap['created_at'] instanceof Carbon
                    ? $recap['created_at']->format('Y-m-d H:i \U\T\C')
                    : 'Unknown time';
                $title = $recap['title'] ?? null;
                $heading = $title ? $title : 'Recap';
                $lines[] = sprintf('### %s — %s (%s)', $heading, $recap['author']['name'], $timestamp);
                $lines[] = $recap['body'];
                $lines[] = '';
            }
        }

        if (count($data['notes']) > 0) {
            $lines[] = '## Notes';
            foreach ($data['notes'] as $note) {
                $timestamp = $note['created_at'] instanceof Carbon
                    ? $note['created_at']->format('Y-m-d H:i \U\T\C')
                    : 'Unknown time';
                $visibility = Str::headline($note['visibility']);
                $lines[] = "### {$note['author']['name']} ({$visibility}) — {$timestamp}";
                $lines[] = $note['content'];
                $lines[] = '';
            }
        }

        if (count($data['dice_rolls']) > 0) {
            $lines[] = '## Dice Rolls';
            foreach ($data['dice_rolls'] as $roll) {
                $timestamp = $roll['created_at'] instanceof Carbon
                    ? $roll['created_at']->format('Y-m-d H:i \U\T\C')
                    : 'Unknown time';
                $lines[] = "- {$roll['roller']['name']} rolled {$roll['expression']} = {$roll['result_total']} ({$timestamp})";
                if (! empty($roll['result_breakdown'])) {
                    $lines[] = '  - Breakdown: ' . json_encode($roll['result_breakdown']);
                }
            }
            $lines[] = '';
        }

        if (count($data['initiative']) > 0) {
            $lines[] = '## Initiative Order';
            foreach ($data['initiative'] as $entry) {
                $lines[] = '- ' . $entry['name'] . ' — Initiative ' . $entry['initiative'] . ' (Dex ' . $entry['dexterity_mod'] . ')' . ($entry['is_current'] ? ' ← current' : '');
            }
            $lines[] = '';
        }

        if (count($data['ai_dialogues']) > 0) {
            $lines[] = '## AI Dialogue Log';
            foreach ($data['ai_dialogues'] as $dialogue) {
                $timestamp = $dialogue['created_at'] instanceof Carbon
                    ? $dialogue['created_at']->format('Y-m-d H:i \U\T\C')
                    : 'Unknown time';
                $header = $dialogue['npc_name']
                    ? $dialogue['npc_name'] . ' (' . ($dialogue['tone'] ?? 'neutral') . ')'
                    : 'NPC dialogue';
                $lines[] = "### {$header} — {$timestamp}";
                $lines[] = '**Prompt**';
                $lines[] = $dialogue['prompt'];
                if ($dialogue['reply']) {
                    $lines[] = '';
                    $lines[] = '**Reply**';
                    $lines[] = $dialogue['reply'];
                }
                $lines[] = '';
            }
        }

        return trim(implode("\n", $lines)) . "\n";
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    protected function formatConditionRounds(array $condition): string
    {
        $rounds = $condition['rounds'] ?? null;

        if ($rounds !== null) {
            $value = (int) $rounds;

            return $value === 1 ? '1 round remaining' : sprintf('%d rounds remaining', $value);
        }

        $hint = $condition['rounds_hint'] ?? null;

        if ($hint) {
            return (string) $hint;
        }

        return 'Duration unknown';
    }

    protected function formatIsoTimestamp(?string $timestamp): string
    {
        if (! $timestamp) {
            return 'Unknown time';
        }

        try {
            return Carbon::parse($timestamp)->setTimezone('UTC')->format('Y-m-d H:i \U\T\C');
        } catch (\Throwable $exception) {
            return $timestamp;
        }
    }

    protected function formatCarbonTimestamp($timestamp): string
    {
        if (! $timestamp instanceof \DateTimeInterface) {
            return 'Unknown time';
        }

        return Carbon::make($timestamp)?->clone()?->setTimezone('UTC')?->format('Y-m-d H:i \U\T\C') ?? 'Unknown time';
    }
}
