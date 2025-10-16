<?php

namespace App\Services;

use App\Events\ConditionTimerSummaryBroadcasted;
use App\Models\Group;
use App\Models\MapToken;
use App\Services\AnalyticsRecorder;
use App\Support\ConditionSummaryCopy;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConditionTimerSummaryProjector
{
    public function __construct(
        private readonly CacheRepository $cache,
        private readonly AnalyticsRecorder $analytics
    )
    {
    }

    public function projectForGroup(Group $group): array
    {
        $key = $this->cacheKey($group->id);

        return $this->cache->remember($key, 300, function () use ($group) {
            Log::info('condition_timer_summary_cache_miss', [
                'group_id' => $group->id,
            ]);

            $summary = $this->buildSummary($group);

            Log::info('condition_timer_summary_cache_rebuilt', [
                'group_id' => $group->id,
                'entries' => count($summary['entries'] ?? []),
            ]);

            return $summary;
        });
    }

    public function refreshForGroup(Group $group, string $trigger = 'manual', bool $broadcast = true): array
    {
        $this->forgetForGroup($group);

        $startedAt = microtime(true);
        $summary = $this->projectForGroup($group);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if ($broadcast) {
            event(new ConditionTimerSummaryBroadcasted($group->id, $summary));
        }

        Log::info('condition_timer_summary_refreshed', [
            'group_id' => $group->id,
            'entries' => count($summary['entries'] ?? []),
        ]);

        $this->analytics->record(
            'timer_summary.refreshed',
            [
                'group_id' => $group->id,
                'trigger' => $trigger,
                'entries_count' => count($summary['entries'] ?? []),
                'duration_ms' => $durationMs,
            ],
            group: $group,
        );

        return $summary;
    }

    public function forgetForGroup(Group $group): void
    {
        $this->cache->forget($this->cacheKey($group->id));
    }

    protected function buildSummary(Group $group): array
    {
        $tokens = MapToken::query()
            ->whereHas('map', fn ($query) => $query->where('group_id', $group->id))
            ->with(['map:id,title,group_id'])
            ->get([
                'id',
                'map_id',
                'name',
                'faction',
                'hidden',
                'status_conditions',
                'status_condition_durations',
            ]);

        $entries = [];

        foreach ($tokens as $token) {
            $conditions = $token->status_conditions ?? [];
            $durations = $token->status_condition_durations ?? [];

            if ($conditions === []) {
                continue;
            }

            $activeConditions = [];

            foreach ($conditions as $condition) {
                $rounds = Arr::get($durations, $condition);

                if ($rounds !== null && (int) $rounds <= 0) {
                    continue;
                }

                $urgency = $this->resolveUrgency($rounds);
                $label = $this->formatConditionLabel($condition);

                $shouldExposeRounds = $this->shouldExposeExactRounds($token->faction, (bool) $token->hidden);

                $summaryText = ConditionSummaryCopy::for(
                    $condition,
                    $urgency,
                    [
                        ':target' => $this->resolveTokenLabel($token->name, (bool) $token->hidden),
                    ]
                );

                $this->analytics->record(
                    'timer_summary.copy_variant',
                    [
                        'condition_key' => $condition,
                        'urgency' => $urgency,
                        'variant_id' => sprintf('%s:%s', $condition, $urgency),
                    ],
                    group: $group,
                );

                $activeConditions[] = [
                    'key' => $condition,
                    'label' => $label,
                    'rounds' => $shouldExposeRounds ? ($rounds === null ? null : (int) $rounds) : null,
                    'rounds_hint' => $shouldExposeRounds ? null : $this->resolveRoundsDescriptor($rounds),
                    'urgency' => $urgency,
                    'summary' => $summaryText,
                ];
            }

            if ($activeConditions === []) {
                continue;
            }

            $entries[] = [
                'map' => [
                    'id' => $token->map->id,
                    'title' => $token->map->title,
                ],
                'token' => [
                    'id' => $token->id,
                    'label' => $this->resolveTokenLabel($token->name, (bool) $token->hidden),
                    'visibility' => $token->hidden ? 'obscured' : 'visible',
                    'disposition' => $this->resolveDisposition($token->faction, (bool) $token->hidden),
                ],
                'conditions' => $this->sortConditions($activeConditions),
            ];
        }

        usort($entries, function (array $a, array $b): int {
            $aUrgency = $a['conditions'][0]['urgency'] ?? 'calm';
            $bUrgency = $b['conditions'][0]['urgency'] ?? 'calm';

            $urgencyOrder = [
                'critical' => 0,
                'warning' => 1,
                'calm' => 2,
            ];

            $aRank = $urgencyOrder[$aUrgency] ?? 3;
            $bRank = $urgencyOrder[$bUrgency] ?? 3;

            if ($aRank !== $bRank) {
                return $aRank <=> $bRank;
            }

            $aLabel = Str::lower($a['token']['label'] ?? '');
            $bLabel = Str::lower($b['token']['label'] ?? '');

            return $aLabel <=> $bLabel;
        });

        return [
            'group_id' => $group->id,
            'generated_at' => CarbonImmutable::now('UTC')->toIso8601String(),
            'entries' => array_values($entries),
        ];
    }

    protected function sortConditions(array $conditions): array
    {
        usort($conditions, function (array $a, array $b): int {
            $urgencyOrder = [
                'critical' => 0,
                'warning' => 1,
                'calm' => 2,
            ];

            $aRank = $urgencyOrder[$a['urgency'] ?? 'calm'] ?? 3;
            $bRank = $urgencyOrder[$b['urgency'] ?? 'calm'] ?? 3;

            if ($aRank !== $bRank) {
                return $aRank <=> $bRank;
            }

            return Str::lower($a['label'] ?? '') <=> Str::lower($b['label'] ?? '');
        });

        return array_values($conditions);
    }

    protected function resolveUrgency(?int $rounds): string
    {
        if ($rounds === null) {
            return 'warning';
        }

        if ($rounds <= 2) {
            return 'critical';
        }

        if ($rounds <= 4) {
            return 'warning';
        }

        return 'calm';
    }

    protected function formatConditionLabel(string $condition): string
    {
        return Str::title(str_replace('_', ' ', $condition));
    }

    protected function resolveTokenLabel(?string $name, bool $hidden): string
    {
        if (! $hidden && filled($name)) {
            return $name;
        }

        return 'Shrouded presence';
    }

    protected function resolveDisposition(?string $faction, bool $hidden): string
    {
        if ($hidden) {
            return 'unknown';
        }

        return match ($faction) {
            MapToken::FACTION_ALLIED => 'ally',
            MapToken::FACTION_NEUTRAL => 'neutral',
            MapToken::FACTION_HAZARD => 'hazard',
            MapToken::FACTION_HOSTILE => 'adversary',
            default => 'unknown',
        };
    }

    protected function shouldExposeExactRounds(?string $faction, bool $hidden): bool
    {
        if ($hidden) {
            return false;
        }

        return in_array($faction, [MapToken::FACTION_ALLIED, MapToken::FACTION_NEUTRAL], true);
    }

    protected function resolveRoundsDescriptor(mixed $rounds): string
    {
        if ($rounds === null) {
            return 'Lingering';
        }

        $value = (int) $rounds;

        if ($value <= 1) {
            return 'Moments remain';
        }

        if ($value <= 3) {
            return 'Waning';
        }

        return 'Holding';
    }

    protected function cacheKey(int $groupId): string
    {
        return sprintf('condition_timer_summary:%d', $groupId);
    }
}
