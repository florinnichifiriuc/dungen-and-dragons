<?php

namespace App\Services;

use App\Models\AiRequest;
use App\Models\Group;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class ConditionMentorBriefingService
{
    public function __construct(
        private readonly ConditionTimerSummaryProjector $projector,
        private readonly ConditionTimerChronicleService $chronicle,
        private readonly AiContentService $ai,
        private readonly ConditionMentorModerationService $moderation
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getBriefing(Group $group): array
    {
        if (! $group->mentor_briefings_enabled) {
            return [];
        }

        $ttl = (int) config('condition-transparency.mentor_briefings.cache_ttl_minutes', 30);
        $cacheKey = sprintf('group:%d:mentor-briefing', $group->id);

        return Cache::remember($cacheKey, now()->addMinutes($ttl), function () use ($group) {
            $pendingQueue = $this->moderation->pendingBriefings($group)->values()->all();
            $playbackDigest = $this->moderation->playbackDigest($group, 10);
            $approved = $this->moderation->latestApproved($group);

            if (! $approved) {
                $summary = $this->projector->projectForGroup($group);
                $focus = $this->buildFocus($group, $summary);
                $ai = $this->ai->mentorBriefing($group, $focus);

                $evaluation = $this->moderation->evaluate($ai['request'], $ai['briefing']);

                if ($evaluation['status'] === AiRequest::MODERATION_APPROVED) {
                    $group->forceFill([
                        'mentor_briefings_last_generated_at' => now('UTC'),
                    ])->save();

                    $approved = $evaluation['request'];
                } else {
                    $pendingQueue = $this->moderation->pendingBriefings($group)->values()->all();

                    return [
                        'focus' => $focus,
                        'briefing' => null,
                        'requested_at' => now('UTC')->toIso8601String(),
                        'moderation' => [
                            'status' => AiRequest::MODERATION_PENDING,
                            'notes' => $evaluation['notes'],
                            'request_id' => $evaluation['request']->id,
                        ],
                        'pending_queue' => $pendingQueue,
                        'playback_digest' => $playbackDigest,
                    ];
                }
            }

            $focus = (array) ($approved->meta['focus'] ?? []);

            return [
                'focus' => $focus,
                'briefing' => $approved->response_text,
                'requested_at' => $approved->completed_at?->toIso8601String(),
                'moderation' => [
                    'status' => $approved->moderation_status,
                    'notes' => $approved->moderation_notes,
                    'request_id' => $approved->id,
                ],
                'pending_queue' => $pendingQueue,
                'playback_digest' => $playbackDigest,
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function catchUpPrompts(Group $group, string|CarbonImmutable|null $since = null, int $limit = 3): array
    {
        if (! $group->mentor_briefings_enabled) {
            return [];
        }

        $threshold = null;

        if ($since instanceof CarbonImmutable) {
            $threshold = $since;
        } elseif (is_string($since) && $since !== '') {
            try {
                $threshold = CarbonImmutable::parse($since, 'UTC');
            } catch (\Throwable) {
                $threshold = null;
            }
        }

        $digest = $this->moderation->approvedDigest($group, $threshold, $limit);

        return collect($digest)
            ->map(function (array $entry) {
                $deliveredAt = $entry['completed_at'] ?? $entry['submitted_at'] ?? null;

                return [
                    'id' => $entry['id'],
                    'delivered_at' => $deliveredAt,
                    'excerpt' => $entry['excerpt'] ?? null,
                    'focus_summary' => $entry['focus_summary'] ?? null,
                ];
            })
            ->filter(fn (array $entry) => ($entry['excerpt'] ?? '') !== null)
            ->values()
            ->all();
    }

    public function setEnabled(Group $group, bool $enabled): void
    {
        $group->forceFill([
            'mentor_briefings_enabled' => $enabled,
        ])->save();

        Cache::forget(sprintf('group:%d:mentor-briefing', $group->id));
    }

    public function flushCache(Group $group): void
    {
        Cache::forget(sprintf('group:%d:mentor-briefing', $group->id));
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    protected function buildFocus(Group $group, array $summary): array
    {
        $critical = [];
        $unacknowledged = [];
        $recurring = [];

        foreach ($summary['entries'] ?? [] as $entry) {
            $tokenLabel = Arr::get($entry, 'token.label', 'Unknown token');

            foreach (Arr::get($entry, 'conditions', []) as $condition) {
                $label = Arr::get($condition, 'label', Arr::get($condition, 'key', 'Unknown condition'));

                if (Arr::get($condition, 'urgency') === 'critical') {
                    $critical[] = sprintf('%s • %s', $tokenLabel, $label);
                }

                $acknowledged = (int) Arr::get($condition, 'acknowledged_count', 0);
                $exposesNumbers = (bool) Arr::get($condition, 'exposes_exact_rounds', false);

                if ($acknowledged === 0 && $exposesNumbers) {
                    $unacknowledged[] = sprintf('%s has not been acknowledged for %s', $tokenLabel, $label);
                }
            }
        }

        $chronicle = $this->chronicle->exportChronicle($group, false, 50);
        $recurringCounts = [];

        foreach ($chronicle as $event) {
            $key = Arr::get($event, 'token.label').'|'.Arr::get($event, 'condition_key');
            $recurringCounts[$key] = ($recurringCounts[$key] ?? 0) + 1;
        }

        foreach ($recurringCounts as $key => $count) {
            if ($count < 3) {
                continue;
            }

            [$token, $condition] = explode('|', $key, 2);
            $recurring[] = sprintf('%s (%s) adjusted %d times recently', $token, $condition, $count);
        }

        return [
            'critical_conditions' => array_values(array_unique($critical)),
            'unacknowledged_tokens' => array_values(array_unique($unacknowledged)),
            'recurring_conditions' => array_values(array_unique($recurring)),
        ];
    }
}
