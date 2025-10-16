<?php

namespace App\Services;

use App\Models\ConditionTimerAdjustment;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\MapToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ConditionTimerChronicleService
{
    public function __construct(private readonly AnalyticsRecorder $analytics)
    {
    }

    /**
     * @param  array<int, array{condition: string, previous: int|null, next: int|null, context?: array<mixed>}>  $adjustments
     * @return array<int, ConditionTimerAdjustment>
     */
    public function recordAdjustments(
        Group $group,
        MapToken $token,
        array $adjustments,
        string $reason,
        ?User $actor = null,
        array $context = []
    ): array {
        if ($adjustments === []) {
            return [];
        }

        $actorId = $actor?->getAuthIdentifier();
        $actorRole = null;

        if ($actorId !== null) {
            $actorRole = GroupMembership::query()
                ->where('group_id', $group->id)
                ->where('user_id', $actorId)
                ->value('role');
        }

        $records = [];

        foreach ($adjustments as $adjustment) {
            $condition = Arr::get($adjustment, 'condition');

            if (! is_string($condition) || $condition === '') {
                continue;
            }

            $previous = $this->normalizeRoundValue(Arr::get($adjustment, 'previous'));
            $next = $this->normalizeRoundValue(Arr::get($adjustment, 'next'));

            if ($previous === $next) {
                continue;
            }

            if ($previous === null && $next === null) {
                continue;
            }

            $delta = $this->calculateDelta($previous, $next);
            $composedContext = $this->mergeContext($context, Arr::get($adjustment, 'context', []));

            $record = ConditionTimerAdjustment::create([
                'group_id' => $group->id,
                'map_token_id' => $token->id,
                'condition_key' => $condition,
                'previous_rounds' => $previous,
                'new_rounds' => $next,
                'delta' => $delta,
                'reason' => $reason,
                'context' => $composedContext,
                'actor_id' => $actorId,
                'actor_role' => $actorRole,
                'recorded_at' => CarbonImmutable::now('UTC'),
            ]);

            $records[] = $record;

            $this->analytics->record(
                'timer_summary.adjusted',
                [
                    'group_id' => $group->id,
                    'map_token_id' => $token->id,
                    'condition_key' => $condition,
                    'reason' => $reason,
                    'delta' => $delta,
                    'previous_rounds' => $previous,
                    'new_rounds' => $next,
                    'context' => $composedContext,
                ],
                $actor,
                $group,
            );
        }

        return $records;
    }

    /**
     * @return array<int, ConditionTimerAdjustment>
     */
    public function recordDiff(
        Group $group,
        MapToken $token,
        array $beforeConditions,
        array $beforeDurations,
        array $afterConditions,
        array $afterDurations,
        string $reason,
        ?User $actor = null,
        array $context = []
    ): array {
        $beforeDurations = $this->normalizeDurationMap($beforeDurations);
        $afterDurations = $this->normalizeDurationMap($afterDurations);

        $conditionKeys = array_unique(array_merge(
            array_keys($beforeDurations),
            array_keys($afterDurations),
            $beforeConditions,
            $afterConditions,
        ));

        $changes = [];

        foreach ($conditionKeys as $condition) {
            if (! is_string($condition) || $condition === '') {
                continue;
            }

            $previous = $beforeDurations[$condition] ?? null;
            $next = $afterDurations[$condition] ?? null;

            if (! in_array($condition, $afterConditions, true)) {
                $next = null;
            }

            if (! in_array($condition, $beforeConditions, true) && ! array_key_exists($condition, $beforeDurations)) {
                $previous = null;
            }

            if ($previous === $next) {
                continue;
            }

            if ($previous === null && $next === null) {
                continue;
            }

            $changes[] = [
                'condition' => $condition,
                'previous' => $previous,
                'next' => $next,
            ];
        }

        if ($changes === []) {
            return [];
        }

        return $this->recordAdjustments($group, $token, $changes, $reason, $actor, $context);
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    public function attachPublicTimeline(Group $group, array $summary, int $perCondition = 5): array
    {
        $entries = $summary['entries'] ?? [];

        if ($entries === []) {
            return $summary;
        }

        $tokenIds = [];
        $conditionKeys = [];

        foreach ($entries as $entry) {
            $tokenId = Arr::get($entry, 'token.id');

            if ($tokenId === null) {
                continue;
            }

            $tokenIds[] = (int) $tokenId;

            foreach (Arr::get($entry, 'conditions', []) as $condition) {
                $key = Arr::get($condition, 'key');

                if (! is_string($key)) {
                    continue;
                }

                $conditionKeys[] = $key;
            }
        }

        $tokenIds = array_values(array_unique($tokenIds));
        $conditionKeys = array_values(array_unique($conditionKeys));

        if ($tokenIds === [] || $conditionKeys === []) {
            return $summary;
        }

        $potentialLimit = max(count($tokenIds) * $perCondition * 2, 50);

        $adjustments = ConditionTimerAdjustment::query()
            ->where('group_id', $group->id)
            ->whereIn('map_token_id', $tokenIds)
            ->whereIn('condition_key', $conditionKeys)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit($potentialLimit)
            ->get();

        $grouped = $adjustments->groupBy(function (ConditionTimerAdjustment $adjustment): string {
            return $this->composeKey($adjustment->map_token_id, $adjustment->condition_key);
        });

        $summary['entries'] = array_map(function (array $entry) use ($grouped, $perCondition) {
            $conditions = Arr::get($entry, 'conditions', []);
            $tokenId = Arr::get($entry, 'token.id');

            $conditions = array_map(function (array $condition) use ($grouped, $tokenId, $perCondition) {
                $conditionKey = Arr::get($condition, 'key');

                if (! is_string($conditionKey) || $tokenId === null) {
                    return $condition;
                }

                $compositeKey = $this->composeKey((int) $tokenId, $conditionKey);
                /** @var Collection<int, ConditionTimerAdjustment>|null $entries */
                $entries = $grouped->get($compositeKey);

                if ($entries === null) {
                    $condition['timeline'] = [];

                    return $condition;
                }

                $exposeNumbers = (bool) Arr::get($condition, 'exposes_exact_rounds', false);

                $condition['timeline'] = $entries
                    ->take($perCondition)
                    ->map(function (ConditionTimerAdjustment $adjustment) use ($exposeNumbers) {
                        return $this->presentAdjustment($adjustment, $exposeNumbers);
                    })
                    ->values()
                    ->all();

                return $condition;
            }, $conditions);

            $entry['conditions'] = $conditions;

            return $entry;
        }, $summary['entries']);

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    public function hydrateSummaryForUser(
        array $summary,
        Group $group,
        User $user,
        bool $canViewSensitive = false
    ): array {
        if (! $canViewSensitive) {
            return $summary;
        }

        $entries = $summary['entries'] ?? [];

        if ($entries === []) {
            return $summary;
        }

        $timelineIds = [];

        foreach ($entries as $entry) {
            foreach (Arr::get($entry, 'conditions', []) as $condition) {
                foreach (Arr::get($condition, 'timeline', []) as $event) {
                    $id = Arr::get($event, 'id');

                    if ($id !== null) {
                        $timelineIds[] = (int) $id;
                    }
                }
            }
        }

        $timelineIds = array_values(array_unique($timelineIds));

        if ($timelineIds === []) {
            return $summary;
        }

        $adjustments = ConditionTimerAdjustment::query()
            ->whereIn('id', $timelineIds)
            ->with('actor')
            ->get()
            ->keyBy('id');

        $summary['entries'] = array_map(function (array $entry) use ($adjustments) {
            $entry['conditions'] = array_map(function (array $condition) use ($adjustments) {
                $condition['timeline'] = array_map(function (array $event) use ($adjustments) {
                    $id = Arr::get($event, 'id');

                    if ($id === null) {
                        return $event;
                    }

                    /** @var ConditionTimerAdjustment|null $adjustment */
                    $adjustment = $adjustments->get((int) $id);

                    if (! $adjustment) {
                        return $event;
                    }

                    $event['detail'] = [
                        'summary' => $this->buildDetailedSummary($adjustment),
                        'previous_rounds' => $adjustment->previous_rounds,
                        'new_rounds' => $adjustment->new_rounds,
                        'delta' => $adjustment->delta,
                        'actor' => $adjustment->actor ? [
                            'id' => $adjustment->actor->id,
                            'name' => $adjustment->actor->name,
                            'role' => $adjustment->actor_role,
                        ] : null,
                        'context' => $adjustment->context,
                    ];

                    return $event;
                }, Arr::get($condition, 'timeline', []));

                return $condition;
            }, Arr::get($entry, 'conditions', []));

            return $entry;
        }, $summary['entries']);

        return $summary;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportChronicle(Group $group, bool $includeSensitive = false, int $limit = 50): array
    {
        $adjustments = ConditionTimerAdjustment::query()
            ->where('group_id', $group->id)
            ->with(['token.map', 'actor'])
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $adjustments
            ->map(function (ConditionTimerAdjustment $adjustment) use ($includeSensitive) {
                $token = $adjustment->token;
                $map = $token?->map;

                $exposeNumbers = $includeSensitive;

                if (! $includeSensitive && $token) {
                    $exposeNumbers = $this->shouldExposeExactRounds($token->faction, (bool) $token->hidden);
                }

                return [
                    'id' => $adjustment->id,
                    'recorded_at' => $adjustment->recorded_at?->clone()->setTimezone('UTC'),
                    'map' => $map ? [
                        'id' => $map->id,
                        'title' => $map->title,
                    ] : null,
                    'token' => $token ? [
                        'id' => $token->id,
                        'label' => $token->name ?? 'Unknown token',
                        'visibility' => $token->hidden ? 'obscured' : 'visible',
                    ] : null,
                    'condition_key' => $adjustment->condition_key,
                    'reason' => $adjustment->reason,
                    'summary' => $this->presentAdjustment($adjustment, $exposeNumbers)['summary'],
                    'previous_rounds' => $includeSensitive ? $adjustment->previous_rounds : null,
                    'new_rounds' => $includeSensitive ? $adjustment->new_rounds : null,
                    'delta' => $includeSensitive ? $adjustment->delta : null,
                    'actor' => $includeSensitive && $adjustment->actor ? [
                        'id' => $adjustment->actor->id,
                        'name' => $adjustment->actor->name,
                        'role' => $adjustment->actor_role,
                    ] : null,
                    'context' => $includeSensitive ? $adjustment->context : null,
                ];
            })
            ->values()
            ->all();
    }

    protected function normalizeRoundValue(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, int|null>
     */
    protected function normalizeDurationMap(?array $values): array
    {
        if ($values === null) {
            return [];
        }

        $normalized = [];

        foreach ($values as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $normalized[$key] = $this->normalizeRoundValue($value);
        }

        return $normalized;
    }

    protected function calculateDelta(?int $previous, ?int $next): ?int
    {
        if ($previous === null && $next === null) {
            return null;
        }

        if ($previous === null && $next !== null) {
            return $next;
        }

        if ($previous !== null && $next === null) {
            return -$previous;
        }

        if ($previous === null || $next === null) {
            return null;
        }

        return $next - $previous;
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>|null  $override
     * @return array<string, mixed>|null
     */
    protected function mergeContext(array $base, ?array $override): ?array
    {
        $merged = array_filter(array_merge($base, $override ?? []), static function ($value) {
            return $value !== null;
        });

        return $merged === [] ? null : $merged;
    }

    protected function composeKey(int $tokenId, string $conditionKey): string
    {
        return sprintf('%d:%s', $tokenId, $conditionKey);
    }

    protected function presentAdjustment(ConditionTimerAdjustment $adjustment, bool $exposeNumbers): array
    {
        $previous = $adjustment->previous_rounds;
        $next = $adjustment->new_rounds;
        $reason = $adjustment->reason;

        $kind = $this->resolveKind($adjustment);
        $summary = $this->buildPublicSummary($kind, $reason, $previous, $next, $exposeNumbers);

        return [
            'id' => $adjustment->id,
            'recorded_at' => $adjustment->recorded_at?->toIso8601String() ?? $adjustment->created_at?->toIso8601String(),
            'reason' => $reason,
            'kind' => $kind,
            'summary' => $summary,
        ];
    }

    protected function resolveKind(ConditionTimerAdjustment $adjustment): string
    {
        $previous = $adjustment->previous_rounds;
        $next = $adjustment->new_rounds;

        if ($adjustment->reason === 'turn_advance' && $next !== null) {
            return 'ticked';
        }

        if ($next === null && $previous !== null) {
            return 'cleared';
        }

        if ($previous === null && $next !== null) {
            return 'started';
        }

        if ($previous !== null && $next !== null) {
            return $next > $previous ? 'extended' : ($next < $previous ? 'reduced' : 'adjusted');
        }

        return 'adjusted';
    }

    protected function buildPublicSummary(
        string $kind,
        string $reason,
        ?int $previous,
        ?int $next,
        bool $exposeNumbers
    ): string {
        return match ($kind) {
            'started' => $exposeNumbers && $next !== null
                ? sprintf('Timer started at %d rounds.', $next)
                : 'Timer started.',
            'extended' => $exposeNumbers && $next !== null
                ? sprintf('Timer extended to %d rounds.', $next)
                : 'Timer extended.',
            'reduced' => $exposeNumbers && $next !== null
                ? sprintf('Timer reduced to %d rounds.', $next)
                : 'Timer reduced.',
            'cleared' => 'Timer cleared.',
            'ticked' => $exposeNumbers && $next !== null
                ? sprintf('Countdown advanced to %d rounds.', $next)
                : 'Countdown advanced.',
            default => match ($reason) {
                'turn_advance' => 'Countdown advanced.',
                default => $exposeNumbers && $next !== null
                    ? sprintf('Timer adjusted to %d rounds.', $next)
                    : 'Timer adjusted.',
            },
        };
    }

    protected function buildDetailedSummary(ConditionTimerAdjustment $adjustment): string
    {
        $reason = $this->describeReason($adjustment->reason);
        $actor = $adjustment->actor?->name ?? 'Automated process';
        $range = $this->describeRange($adjustment->previous_rounds, $adjustment->new_rounds);

        return trim(sprintf('%s by %s%s', ucfirst($reason), $actor, $range ? ': '.$range : ''));
    }

    protected function describeReason(string $reason): string
    {
        return match ($reason) {
            'manual_adjustment' => 'manual adjustment',
            'token_update' => 'token update',
            'token_created' => 'token placement',
            'turn_advance' => 'turn advance',
            default => str_replace('_', ' ', $reason),
        };
    }

    protected function describeRange(?int $previous, ?int $next): string
    {
        if ($previous === null && $next === null) {
            return 'set to linger';
        }

        if ($previous === null && $next !== null) {
            return sprintf('started at %d rounds', $next);
        }

        if ($previous !== null && $next === null) {
            return sprintf('cleared (was %d rounds)', $previous);
        }

        if ($previous !== null && $next !== null) {
            return sprintf('%d → %d rounds', $previous, $next);
        }

        return '';
    }

    protected function shouldExposeExactRounds(?string $faction, bool $hidden): bool
    {
        if ($hidden) {
            return false;
        }

        return in_array($faction, [
            MapToken::FACTION_ALLIED,
            MapToken::FACTION_NEUTRAL,
        ], true);
    }
}
