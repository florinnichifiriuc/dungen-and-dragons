<?php

namespace App\Http\Controllers;

use App\Events\MapTokenBroadcasted;
use App\Http\Requests\MapTokenConditionTimerBatchRequest;
use App\Models\Group;
use App\Models\Map;
use App\Models\MapToken;
use App\Services\ConditionTimerSummaryProjector;
use App\Support\Broadcasting\MapTokenPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MapTokenConditionTimerBatchController extends Controller
{
    public function __invoke(
        MapTokenConditionTimerBatchRequest $request,
        Group $group,
        Map $map
    ): RedirectResponse {
        $this->assertMapForGroup($group, $map);

        $adjustments = collect($request->validated('adjustments'));

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

        foreach ($tokenIds as $tokenId) {
            $tokenAdjustments = $adjustments->where('token_id', $tokenId);
            $token = $tokens->get($tokenId);

            if (! $token) {
                $conflicts += $this->recordMissingTokenConflicts(
                    $request,
                    $group,
                    $map,
                    (int) $tokenId,
                    $tokenAdjustments
                );

                continue;
            }

            $this->authorize('update', $token);

            $statusConditions = $token->status_conditions ?? [];
            $durations = $token->status_condition_durations ?? [];
            $dirty = false;

            foreach ($tokenAdjustments as $adjustment) {
                $condition = $adjustment['condition'] ?? null;

                if (! is_string($condition)) {
                    continue;
                }

                if (! in_array($condition, $statusConditions, true)) {
                    $this->logConflict(
                        $request,
                        'condition_missing',
                        $group,
                        $map,
                        (int) $tokenId,
                        $condition
                    );

                    $conflicts++;

                    continue;
                }

                $current = $durations[$condition] ?? null;
                $expected = $adjustment['expected_rounds'] ?? null;

                if ($expected !== null) {
                    if ($current === null) {
                        $this->logConflict(
                            $request,
                            'timer_absent',
                            $group,
                            $map,
                            (int) $tokenId,
                            $condition,
                            [
                                'expected' => $expected,
                                'actual' => null,
                            ]
                        );

                        $conflicts++;

                        continue;
                    }

                    if ((int) $current !== (int) $expected) {
                        $this->logConflict(
                            $request,
                            'expected_mismatch',
                            $group,
                            $map,
                            (int) $tokenId,
                            $condition,
                            [
                                'expected' => (int) $expected,
                                'actual' => (int) $current,
                            ]
                        );

                        $conflicts++;

                        continue;
                    }
                }

                $target = $this->resolveTargetValue($current, $adjustment);

                if ($target === null) {
                    continue;
                }

                if ($target <= 0) {
                    if (array_key_exists($condition, $durations)) {
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
                    $this->logConflict(
                        $request,
                        'clamped_to_bounds',
                        $group,
                        $map,
                        (int) $tokenId,
                        $condition,
                        [
                            'requested' => (int) round($target),
                            'applied' => $clamped,
                        ]
                    );
                }

                if ($current !== null && (int) $current === $clamped) {
                    continue;
                }

                $durations[$condition] = $clamped;
                $dirty = true;
                $applied++;
            }

            if (! $dirty) {
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

            $broadcastTokens[] = $token->fresh();
        }

        foreach ($broadcastTokens as $token) {
            event(new MapTokenBroadcasted($map, 'updated', MapTokenPayload::from($token)));
        }

        if ($applied > 0) {
            app(ConditionTimerSummaryProjector::class)->refreshForGroup($group);
        }

        $messageParts = [];

        if ($applied > 0) {
            $messageParts[] = sprintf('%d timer%s adjusted', $applied, $applied === 1 ? '' : 's');
        }

        if ($conflicts > 0) {
            $messageParts[] = sprintf('%d conflict%s logged', $conflicts, $conflicts === 1 ? '' : 's');
        }

        if ($messageParts === []) {
            $messageParts[] = 'No timers updated';
        }

        return redirect()
            ->route('groups.maps.show', [$group, $map])
            ->with('success', implode(' · ', $messageParts).'.');
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
        Collection $adjustments
    ): int {
        $conflicts = 0;

        foreach ($adjustments as $adjustment) {
            $condition = $adjustment['condition'] ?? null;

            if (! is_string($condition)) {
                continue;
            }

            $this->logConflict(
                $request,
                'token_missing',
                $group,
                $map,
                $tokenId,
                $condition
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
        array $context = []
    ): void {
        Log::warning('condition_timer_batch_adjustment_conflict', array_merge([
            'reason' => $reason,
            'group_id' => $group->id,
            'map_id' => $map->id,
            'token_id' => $tokenId,
            'condition' => $condition,
            'user_id' => $request->user()?->getAuthIdentifier(),
        ], $context));
    }

    protected function assertMapForGroup(Group $group, Map $map): void
    {
        abort_if($map->group_id !== $group->id, 404);
    }
}

