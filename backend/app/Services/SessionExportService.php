<?php

namespace App\Services;

use App\Models\AiRequest;
use App\Models\CampaignSession;
use App\Models\SessionNote;
use App\Models\User;
use App\Policies\CampaignPolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SessionExportService
{
    public function __construct(private readonly CampaignPolicy $campaignPolicy)
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

        $storedRecordingUrl = null;
        $storedRecordingName = null;

        if ($session->hasStoredRecording()) {
            $storedRecordingUrl = Storage::disk($session->recording_disk)->url($session->recording_path);
            $storedRecordingName = basename($session->recording_path);
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
}
