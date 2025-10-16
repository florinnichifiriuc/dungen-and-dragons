<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\ConditionTimerAcknowledgement;
use App\Models\ConditionTimerAdjustment;
use App\Models\Group;
use App\Models\MapToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class FacilitatorInsightsService
{
    public function __construct(
        private readonly ConditionTimerSummaryProjector $summaryProjector,
        private readonly ConditionTimerAcknowledgementService $acknowledgements,
        private readonly AnalyticsRecorder $analytics,
    ) {
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return array<string, mixed>
     */
    public function build(Campaign $campaign, User $viewer, array $filters = []): array
    {
        /** @var Group $group */
        $group = $campaign->group()->firstOrFail();

        $urgencyFilter = $this->validatedFilter($filters['urgency'] ?? null, ['critical', 'warning', 'calm']);
        $factionFilter = $this->validatedFilter($filters['faction'] ?? null, ['ally', 'adversary', 'neutral', 'hazard', 'unknown']);

        $summary = $this->summaryProjector->projectForGroup($group);
        $summary = $this->acknowledgements->hydrateSummaryForUser($summary, $group, $viewer, true);

        $conditions = $this->extractConditions($summary, $urgencyFilter, $factionFilter);

        $metrics = $this->buildMetrics($conditions);
        $repeatOffenders = $this->identifyRepeatOffenders($group);
        $atRiskPlayers = $this->identifyAtRiskPlayers($conditions);
        $averageResponseMinutes = $this->calculateAverageAcknowledgementMinutes($group);

        $generatedAt = CarbonImmutable::now('UTC');

        $insights = [
            'generated_at' => $generatedAt->toIso8601String(),
            'filters' => [
                'urgency' => $urgencyFilter,
                'faction' => $factionFilter,
            ],
            'metrics' => array_merge($metrics, [
                'average_acknowledgement_minutes' => $averageResponseMinutes,
            ]),
            'conditions' => $conditions->values()->all(),
            'repeat_offenders' => $repeatOffenders,
            'at_risk_players' => $atRiskPlayers,
            'exports' => [
                'markdown' => $this->buildMarkdownExport($metrics, $repeatOffenders, $atRiskPlayers, $averageResponseMinutes),
            ],
        ];

        $this->analytics->record(
            'facilitator_insights.viewed',
            [
                'campaign_id' => $campaign->id,
                'filters' => [
                    'urgency' => $urgencyFilter ?? 'all',
                    'faction' => $factionFilter ?? 'all',
                ],
                'total_conditions' => $metrics['total_active'] ?? 0,
                'critical_unacknowledged' => $metrics['critical_unacknowledged'] ?? 0,
            ],
            actor: $viewer,
            group: $group,
        );

        return $insights;
    }

    protected function validatedFilter(?string $value, array $allowed): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return in_array($value, $allowed, true) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return Collection<int, array<string, mixed>>
     */
    protected function extractConditions(array $summary, ?string $urgencyFilter, ?string $factionFilter): Collection
    {
        $entries = collect($summary['entries'] ?? []);

        return $entries
            ->flatMap(function (array $entry) use ($urgencyFilter, $factionFilter) {
                $token = Arr::get($entry, 'token', []);
                $conditions = Arr::get($entry, 'conditions', []);

                $disposition = $token['disposition'] ?? 'unknown';

                if ($factionFilter !== null && $disposition !== $factionFilter) {
                    return [];
                }

                return collect($conditions)
                    ->filter(function (array $condition) use ($urgencyFilter) {
                        if ($urgencyFilter === null) {
                            return true;
                        }

                        return ($condition['urgency'] ?? null) === $urgencyFilter;
                    })
                    ->map(function (array $condition) use ($entry, $token, $disposition) {
                        $acknowledgedCount = (int) ($condition['acknowledged_count'] ?? 0);

                        return [
                            'map' => $entry['map'] ?? null,
                            'token' => [
                                'id' => $token['id'] ?? null,
                                'label' => $token['label'] ?? 'Unknown token',
                                'disposition' => $disposition,
                                'visibility' => $token['visibility'] ?? 'visible',
                            ],
                            'condition' => [
                                'key' => $condition['key'] ?? null,
                                'label' => $condition['label'] ?? null,
                                'urgency' => $condition['urgency'] ?? null,
                                'summary' => $condition['summary'] ?? null,
                                'rounds' => $condition['rounds'] ?? null,
                                'rounds_hint' => $condition['rounds_hint'] ?? null,
                            ],
                            'acknowledged_count' => $acknowledgedCount,
                            'acknowledged_by_viewer' => (bool) ($condition['acknowledged_by_viewer'] ?? false),
                        ];
                    })
                    ->all();
            })
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $conditions
     * @return array<string, int>
     */
    protected function buildMetrics(Collection $conditions): array
    {
        $total = $conditions->count();
        $acknowledged = $conditions->filter(fn ($condition) => ($condition['acknowledged_count'] ?? 0) > 0)->count();
        $criticalUnack = $conditions
            ->filter(fn ($condition) => ($condition['condition']['urgency'] ?? null) === 'critical' && ($condition['acknowledged_count'] ?? 0) === 0)
            ->count();
        $warningUnack = $conditions
            ->filter(fn ($condition) => ($condition['condition']['urgency'] ?? null) === 'warning' && ($condition['acknowledged_count'] ?? 0) === 0)
            ->count();

        return [
            'total_active' => $total,
            'acknowledged' => $acknowledged,
            'unacknowledged' => $total - $acknowledged,
            'critical_unacknowledged' => $criticalUnack,
            'warning_unacknowledged' => $warningUnack,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function identifyRepeatOffenders(Group $group): array
    {
        $windowStart = CarbonImmutable::now('UTC')->subDays(7);

        $adjustments = ConditionTimerAdjustment::query()
            ->where('group_id', $group->id)
            ->where('recorded_at', '>=', $windowStart)
            ->selectRaw('map_token_id, COUNT(*) as adjustments_count')
            ->groupBy('map_token_id')
            ->having('adjustments_count', '>=', 3)
            ->orderByDesc('adjustments_count')
            ->limit(10)
            ->get();

        if ($adjustments->isEmpty()) {
            return [];
        }

        $tokenIds = $adjustments->pluck('map_token_id')->filter()->unique()->values();

        $tokens = MapToken::query()
            ->with('map:id,title')
            ->whereIn('id', $tokenIds)
            ->get(['id', 'map_id', 'name', 'faction', 'hidden', 'status_conditions', 'status_condition_durations']);

        $conditionCounts = ConditionTimerAdjustment::query()
            ->where('group_id', $group->id)
            ->where('recorded_at', '>=', $windowStart)
            ->whereIn('map_token_id', $tokenIds)
            ->get(['map_token_id', 'condition_key'])
            ->groupBy('map_token_id')
            ->map(function (Collection $entries) {
                return collect($entries)
                    ->groupBy('condition_key')
                    ->map(fn (Collection $grouped) => $grouped->count())
                    ->sortDesc()
                    ->take(3)
                    ->map(fn (int $count, string $condition) => [
                        'condition' => $condition,
                        'count' => $count,
                    ])
                    ->values()
                    ->all();
            });

        return $adjustments->map(function ($adjustment) use ($tokens, $conditionCounts) {
            $token = $tokens->firstWhere('id', $adjustment->map_token_id);

            return [
                'token' => [
                    'id' => $token?->id,
                    'label' => $this->resolveTokenLabel($token),
                    'disposition' => $this->resolveDisposition($token),
                    'map' => $token?->map ? [
                        'id' => $token->map->id,
                        'title' => $token->map->title,
                    ] : null,
                ],
                'adjustments_count' => (int) $adjustment->adjustments_count,
                'recent_conditions' => $conditionCounts->get($adjustment->map_token_id, []),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $conditions
     * @return array<int, array<string, mixed>>
     */
    protected function identifyAtRiskPlayers(Collection $conditions): array
    {
        return $conditions
            ->filter(fn ($condition) => ($condition['token']['disposition'] ?? null) === 'ally')
            ->filter(fn ($condition) => in_array($condition['condition']['urgency'] ?? null, ['critical', 'warning'], true))
            ->filter(fn ($condition) => ($condition['acknowledged_count'] ?? 0) === 0)
            ->groupBy(fn ($condition) => $condition['token']['id'] ?? spl_object_hash((object) $condition))
            ->map(function (Collection $entries) {
                $first = $entries->first();

                return [
                    'token' => $first['token'] ?? null,
                    'conditions' => $entries->pluck('condition')->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function calculateAverageAcknowledgementMinutes(Group $group): ?float
    {
        $windowStart = CarbonImmutable::now('UTC')->subDays(14);

        $acknowledgements = ConditionTimerAcknowledgement::query()
            ->where('group_id', $group->id)
            ->whereNotNull('summary_generated_at')
            ->whereNotNull('acknowledged_at')
            ->where('acknowledged_at', '>=', $windowStart)
            ->get(['summary_generated_at', 'acknowledged_at']);

        if ($acknowledgements->isEmpty()) {
            return null;
        }

        $minutes = $acknowledgements
            ->map(function (ConditionTimerAcknowledgement $acknowledgement): float {
                $generated = CarbonImmutable::parse($acknowledgement->summary_generated_at);
                $acknowledged = CarbonImmutable::parse($acknowledgement->acknowledged_at);

                $diff = $generated->diffInSeconds($acknowledged, false);

                if ($diff < 0) {
                    $diff = abs($diff);
                }

                return round($diff / 60, 2);
            })
            ->filter(fn ($value) => $value >= 0)
            ->values();

        if ($minutes->isEmpty()) {
            return null;
        }

        return round($minutes->average(), 2);
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @param  array<int, array<string, mixed>>  $repeatOffenders
     * @param  array<int, array<string, mixed>>  $atRiskPlayers
     */
    protected function buildMarkdownExport(array $metrics, array $repeatOffenders, array $atRiskPlayers, ?float $averageMinutes): string
    {
        $lines = [];

        $lines[] = '# Facilitator condition timer insights';
        $lines[] = '';
        $lines[] = sprintf('- Active timers tracked: **%d**', $metrics['total_active'] ?? 0);
        $lines[] = sprintf('- Acknowledged: **%d**', $metrics['acknowledged'] ?? 0);
        $lines[] = sprintf('- Unacknowledged: **%d**', $metrics['unacknowledged'] ?? 0);
        $lines[] = sprintf('- Critical without acknowledgement: **%d**', $metrics['critical_unacknowledged'] ?? 0);
        $lines[] = sprintf('- Warning without acknowledgement: **%d**', $metrics['warning_unacknowledged'] ?? 0);

        if ($averageMinutes !== null) {
            $lines[] = sprintf('- Average acknowledgement time (14d window): **%s minutes**', number_format($averageMinutes, 2));
        }

        if ($repeatOffenders !== []) {
            $lines[] = '';
            $lines[] = '## Repeat offenders (7 day window)';
            foreach ($repeatOffenders as $offender) {
                $tokenLabel = $offender['token']['label'] ?? 'Unknown token';
                $adjustments = $offender['adjustments_count'] ?? 0;
                $mapTitle = $offender['token']['map']['title'] ?? 'Unknown map';
                $lines[] = sprintf('- **%s** (%s): %d adjustments', $tokenLabel, $mapTitle, $adjustments);
            }
        }

        if ($atRiskPlayers !== []) {
            $lines[] = '';
            $lines[] = '## At-risk allies needing nudges';
            foreach ($atRiskPlayers as $player) {
                $label = Arr::get($player, 'token.label', 'Unknown ally');
                $conditions = collect($player['conditions'] ?? [])
                    ->map(fn ($condition) => $condition['label'] ?? $condition['key'] ?? 'Condition')
                    ->join(', ');
                $lines[] = sprintf('- **%s**: %s', $label, $conditions);
            }
        }

        return implode("\n", $lines);
    }

    protected function resolveTokenLabel(?MapToken $token): string
    {
        if ($token === null) {
            return 'Unknown token';
        }

        if (! $token->hidden && filled($token->name)) {
            return $token->name;
        }

        return 'Shrouded presence';
    }

    protected function resolveDisposition(?MapToken $token): string
    {
        if ($token === null) {
            return 'unknown';
        }

        if ($token->hidden) {
            return 'unknown';
        }

        return match ($token->faction) {
            MapToken::FACTION_ALLIED => 'ally',
            MapToken::FACTION_NEUTRAL => 'neutral',
            MapToken::FACTION_HAZARD => 'hazard',
            MapToken::FACTION_HOSTILE => 'adversary',
            default => 'unknown',
        };
    }
}
