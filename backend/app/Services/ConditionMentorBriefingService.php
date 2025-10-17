<?php

namespace App\Services;

use App\Models\Group;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class ConditionMentorBriefingService
{
    public function __construct(
        private readonly ConditionTimerSummaryProjector $projector,
        private readonly ConditionTimerChronicleService $chronicle,
        private readonly AiContentService $ai
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
            $summary = $this->projector->projectForGroup($group);
            $focus = $this->buildFocus($group, $summary);
            $ai = $this->ai->mentorBriefing($group, $focus);

            $group->forceFill([
                'mentor_briefings_last_generated_at' => now('UTC'),
            ])->save();

            return [
                'focus' => $focus,
                'briefing' => $ai['briefing'],
                'requested_at' => now('UTC')->toIso8601String(),
            ];
        });
    }

    public function setEnabled(Group $group, bool $enabled): void
    {
        $group->forceFill([
            'mentor_briefings_enabled' => $enabled,
        ])->save();

        if (! $enabled) {
            Cache::forget(sprintf('group:%d:mentor-briefing', $group->id));
        }
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
