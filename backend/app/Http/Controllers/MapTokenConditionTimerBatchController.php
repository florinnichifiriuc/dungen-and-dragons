<?php

namespace App\Http\Controllers;

use App\Events\MapTokenBroadcasted;
use App\Http\Requests\MapTokenConditionTimerBatchRequest;
use App\Models\Group;
use App\Models\Map;
use App\Models\MapToken;
use App\Services\AnalyticsRecorder;
use App\Services\ConditionTimerChronicleService;
use App\Services\ConditionTimerSummaryProjector;
use App\Support\Broadcasting\MapTokenPayload;
use App\Support\ConditionTimerRateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MapTokenConditionTimerBatchController extends Controller
{
    public function __construct(
        private readonly AnalyticsRecorder $analytics,
        private readonly ConditionTimerSummaryProjector $conditionTimerSummaryProjector,
        private readonly ConditionTimerChronicleService $chronicle,
        private readonly ConditionTimerRateLimiter $rateLimiter
    ) {
    }

    public function __invoke(
        MapTokenConditionTimerBatchRequest $request,
        Group $group,
        Map $map
    ): RedirectResponse {
        $this->assertMapForGroup($group, $map);

        $adjustments = collect($request->validated('adjustments'));
        $selectionCount = max(1, $adjustments->count());

        if ($cooldown = $this->rateLimiter->cooldownFor($request->user(), $map)) {
            $this->analytics->record(
                'timer_summary.circuit_cooldown_active',
                [
                    'group_id' => $group->id,
                    'map_id' => $map->id,
                    'remaining_seconds' => $cooldown,
                ],
                actor: $request->user(),
                group: $group,
            );

            return redirect()
                ->route('groups.maps.show', [$group, $map])
                ->with('error', trans('app.condition_timer_circuit_active', ['seconds' => $cooldown]));
        }

        $tokenHits = $adjustments
            ->groupBy('token_id')
            ->map(fn (Collection $entries) => $entries->count())
            ->filter(fn ($count, $tokenId) => $tokenId !== null)
            ->mapWithKeys(fn ($count, $tokenId) => [(int) $tokenId => $count])
            ->all();

        if ($violation = $this->rateLimiter->check($request->user(), $map, $tokenHits, $selectionCount)) {
            $this->analytics->record(
                'timer_summary.rate_limited',
                [
                    'group_id' => $group->id,
                    'map_id' => $map->id,
                    'scope' => $violation['scope'],
                    'token_id' => $violation['scope'] === 'token' ? $violation['token_id'] : null,
                    'available_in' => $violation['available_in'],
                    'suggested_backoff' => $violation['suggested_backoff'],
                    'lockouts' => $violation['lockouts'],
                ],
                actor: $request->user(),
                group: $group,
            );

            return redirect()
                ->route('groups.maps.show', [$group, $map])
                ->with('error', trans_choice(
                    'app.condition_timer_rate_limited',
                    max(1, (int) $violation['suggested_backoff']),
                    ['seconds' => $violation['suggested_backoff']]
                ));
        }

        $this->rateLimiter->hit($request->user(), $map, $tokenHits);

        if ($adjustments->isEmpty()) {
            return redirect()->route('groups.maps.show', [$group, $map]);
        }

        $tokenIds = $adjustments
            ->pluck('token_id')
            ->filter()
            ->unique()
            ->values();

        $tokens = MapToken::query()
            ->where('map_id', $map->id)
            ->whereIn('id', $tokenIds)
            ->get()
            ->keyBy('id');

        $conflicts = 0;
        $applied = 0;
        $broadcastTokens = [];
        $conflictMessages = [];
        $circuitBreakerTriggered = false;
        $conflictThreshold = $this->resolveConflictThreshold($selectionCount);

        foreach ($tokenIds as $tokenId) {
            $tokenAdjustments = $adjustments->where('token_id', $tokenId);
            $token = $tokens->get($tokenId);

            if (! $token) {
                $conflicts += $this->recordMissingTokenConflicts(
                    $request,
                    $group,
                    $map,
                    (int) $tokenId,
                    $tokenAdjustments,
                    $selectionCount,
                    $conflictMessages
                );

                if ($conflicts >= $conflictThreshold) {
                    $circuitBreakerTriggered = true;

                    break;
                }

                continue;
            }

            $this->authorize('update', $token);

            $statusConditions = $token->status_conditions ?? [];
            $durations = $token->status_condition_durations ?? [];
            $dirty = false;
            $tokenEvents = [];

            foreach ($tokenAdjustments as $adjustment) {
                $condition = $adjustment['condition'] ?? null;

                if (! is_string($condition)) {
                    continue;
                }

                if (! in_array($condition, $statusConditions, true)) {
                    $conflictMessages[] = $this->logConflict(
                        $request,
                        'condition_missing',
                        $group,
                        $map,
                        (int) $tokenId,
                        $condition,
                        $selectionCount,
                        ['token_name' => $token->name]
                    );

                    $conflicts++;

                    if ($conflicts >= $conflictThreshold) {
                        $circuitBreakerTriggered = true;

                        break;
                    }

                    continue;
                }

                $current = $durations[$condition] ?? null;
                $expected = $adjustment['expected_rounds'] ?? null;

                if ($expected !== null) {
                    if ($current === null) {
                        $conflictMessages[] = $this->logConflict(
                            $request,
                            'timer_absent',
                            $group,
                            $map,
                            (int) $tokenId,
                            $condition,
                            $selectionCount,
                            [
                                'expected' => $expected,
                                'actual' => null,
                                'token_name' => $token->name,
                            ]
                        );

                        $conflicts++;

                        if ($conflicts >= $conflictThreshold) {
                            $circuitBreakerTriggered = true;

                            break;
                        }

                        continue;
                    }

                    if ((int) $current !== (int) $expected) {
                        $conflictMessages[] = $this->logConflict(
                            $request,
                            'expected_mismatch',
                            $group,
                            $map,
                            (int) $tokenId,
                            $condition,
                            $selectionCount,
                            [
                                'expected' => (int) $expected,
                                'actual' => (int) $current,
                                'token_name' => $token->name,
                            ]
                        );

                        $conflicts++;

                        if ($conflicts >= $conflictThreshold) {
                            $circuitBreakerTriggered = true;

                            break;
                        }

                        continue;
                    }
                }

                $target = $this->resolveTargetValue($current, $adjustment);

                if ($target === null) {
                    continue;
                }

                if ($target <= 0) {
                    if (array_key_exists($condition, $durations)) {
                        $tokenEvents[] = [
                            'condition' => $condition,
                            'previous' => $durations[$condition] ?? null,
                            'next' => null,
                            'context' => ['cleared' => true],
                        ];
                        unset($durations[$condition]);
                        $dirty = true;
                        $applied++;
                    }

                    continue;
                }

                $clamped = min(
                    max((int) round($target), 1),
                    MapToken::MAX_CONDITION_DURATION
                );

                if ($clamped !== (int) round($target)) {
                    $conflictMessages[] = $this->logConflict(
                        $request,
                        'clamped_to_bounds',
                        $group,
                        $map,
                        (int) $tokenId,
                        $condition,
                        $selectionCount,
                        [
                            'requested' => (int) round($target),
                            'applied' => $clamped,
                            'token_name' => $token->name,
                        ]
                    );
                }

                if ($current !== null && (int) $current === $clamped) {
                    continue;
                }

                $tokenEvents[] = [
                    'condition' => $condition,
                    'previous' => $current,
                    'next' => $clamped,
                ];

                $durations[$condition] = $clamped;
                $dirty = true;
                $applied++;
            }

            if (! $dirty) {
                if ($circuitBreakerTriggered) {
                    break;
                }

                continue;
            }

            $orderedDurations = [];

            foreach (MapToken::CONDITIONS as $condition) {
                if (array_key_exists($condition, $durations)) {
                    $orderedDurations[$condition] = (int) $durations[$condition];
                }
            }

            $token->forceFill([
                'status_condition_durations' => $orderedDurations === [] ? null : $orderedDurations,
            ])->save();

            if ($tokenEvents !== []) {
                $this->chronicle->recordAdjustments(
                    $group,
                    $token,
                    $tokenEvents,
                    'manual_adjustment',
                    $request->user(),
                    ['source' => 'batch_adjustment'],
                );
            }

            $broadcastTokens[] = $token->fresh();

            if ($circuitBreakerTriggered) {
                break;
            }
        }

        if ($circuitBreakerTriggered) {
            $cooldown = $this->rateLimiter->triggerCircuit($request->user(), $map);

            $this->analytics->record(
                'timer_summary.circuit_breaker_triggered',
                [
                    'group_id' => $group->id,
                    'map_id' => $map->id,
                    'conflicts' => $conflicts,
                    'applied' => $applied,
                    'selection_count' => $selectionCount,
                    'cooldown_seconds' => $cooldown,
                ],
                actor: $request->user(),
                group: $group,
            );
        }

        foreach ($broadcastTokens as $token) {
            event(new MapTokenBroadcasted($map, 'updated', MapTokenPayload::from($token)));
        }

        if ($applied > 0) {
            $this->conditionTimerSummaryProjector->refreshForGroup($group, 'batch_adjustment');
        }

        $messageParts = [];

        if ($applied > 0) {
            $messageParts[] = sprintf('%d timer%s adjusted', $applied, $applied === 1 ? '' : 's');
        }

        if ($conflicts > 0) {
            $messageParts[] = sprintf('%d conflict%s logged', $conflicts, $conflicts === 1 ? '' : 's');
        }

        if ($messageParts === []) {
            $messageParts[] = trans('app.condition_timer_no_updates');
        }

        $redirect = redirect()->route('groups.maps.show', [$group, $map]);

        if ($messageParts !== []) {
            $redirect->with('success', implode(' · ', $messageParts).'.');
        }

        if ($conflictMessages !== []) {
            $redirect->with('condition_timer_conflicts', array_values(array_filter($conflictMessages)));
        }

        if ($circuitBreakerTriggered) {
            $cooldown = $this->rateLimiter->cooldownFor($request->user(), $map)
                ?? config('condition_timers.circuit_breaker.cooldown_seconds', 120);

            $redirect->with('error', trans('app.condition_timer_circuit_tripped', [
                'seconds' => $cooldown,
            ]));
        }

        if ($conflicts > 0 && $applied === 0) {
            $this->analytics->record(
                'timer_summary.anomaly_detected',
                [
                    'group_id' => $group->id,
                    'map_id' => $map->id,
                    'reason' => 'all_conflicts',
                    'selection_count' => $selectionCount,
                ],
                actor: $request->user(),
                group: $group,
            );
        }

        return $redirect;
    }

    protected function resolveTargetValue($current, array $adjustment): ?int
    {
        if (array_key_exists('delta', $adjustment) && $adjustment['delta'] !== null) {
            return (int) ($current ?? 0) + (int) $adjustment['delta'];
        }

        if (array_key_exists('set_to', $adjustment) && $adjustment['set_to'] !== null) {
            return (int) $adjustment['set_to'];
        }

        return null;
    }

    protected function recordMissingTokenConflicts(
        MapTokenConditionTimerBatchRequest $request,
        Group $group,
        Map $map,
        int $tokenId,
        Collection $adjustments,
        int $selectionCount,
        array &$conflictMessages
    ): int {
        $conflicts = 0;

        foreach ($adjustments as $adjustment) {
            $condition = $adjustment['condition'] ?? null;

            if (! is_string($condition)) {
                continue;
            }

            $conflictMessages[] = $this->logConflict(
                $request,
                'token_missing',
                $group,
                $map,
                $tokenId,
                $condition,
                $selectionCount
            );

            $conflicts++;
        }

        return $conflicts;
    }

    protected function logConflict(
        MapTokenConditionTimerBatchRequest $request,
        string $reason,
        Group $group,
        Map $map,
        int $tokenId,
        ?string $condition,
        int $selectionCount,
        array $context = []
    ): string {
        $context = array_merge([
            'token_name' => null,
        ], $context);

        $logContext = array_merge([
            'reason' => $reason,
            'group_id' => $group->id,
            'map_id' => $map->id,
            'token_id' => $tokenId,
            'condition' => $condition,
            'user_id' => $request->user()?->getAuthIdentifier(),
        ], $context);

        Log::warning('condition_timer_batch_adjustment_conflict', $logContext);

        $this->analytics->record(
            'timer_summary.conflict',
            [
                'group_id' => $group->id,
                'conflict_type' => $reason,
                'selection_count' => $selectionCount,
                'resolved' => false,
            ],
            actor: $request->user(),
            group: $group,
        );

        return $this->formatConflictMessage($reason, $logContext);
    }

    protected function assertMapForGroup(Group $group, Map $map): void
    {
        abort_if($map->group_id !== $group->id, 404);
    }

    protected function resolveConflictThreshold(int $selectionCount): int
    {
        $ratio = config('condition_timers.circuit_breaker.conflict_ratio', 0.6);
        $minimum = config('condition_timers.circuit_breaker.minimum_conflicts', 3);

        return max($minimum, (int) ceil($selectionCount * $ratio));
    }

    protected function formatConflictMessage(string $reason, array $context): string
    {
        return match ($reason) {
            'token_missing' => trans('app.condition_timer_conflicts.token_missing', [
                'token' => $context['token_name'] ?? '#'.$context['token_id'],
            ]),
            'condition_missing' => trans('app.condition_timer_conflicts.condition_missing', [
                'token' => $context['token_name'] ?? '#'.$context['token_id'],
                'condition' => $context['condition'] ?? trans('app.condition_timer_unknown'),
            ]),
            'timer_absent' => trans('app.condition_timer_conflicts.timer_absent', [
                'token' => $context['token_name'] ?? '#'.$context['token_id'],
                'condition' => $context['condition'] ?? trans('app.condition_timer_unknown'),
            ]),
            'expected_mismatch' => trans('app.condition_timer_conflicts.expected_mismatch', [
                'token' => $context['token_name'] ?? '#'.$context['token_id'],
                'condition' => $context['condition'] ?? trans('app.condition_timer_unknown'),
                'expected' => $context['expected'] ?? trans('app.condition_timer_unknown'),
                'actual' => $context['actual'] ?? trans('app.condition_timer_unknown'),
            ]),
            'clamped_to_bounds' => trans('app.condition_timer_conflicts.clamped_to_bounds', [
                'token' => $context['token_name'] ?? '#'.$context['token_id'],
                'condition' => $context['condition'] ?? trans('app.condition_timer_unknown'),
                'applied' => $context['applied'] ?? trans('app.condition_timer_unknown'),
                'requested' => $context['requested'] ?? trans('app.condition_timer_unknown'),
            ]),
            default => trans('app.condition_timer_conflicts.generic'),
        };
    }
}

