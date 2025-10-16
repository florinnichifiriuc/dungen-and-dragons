<?php

namespace App\Services;

use App\Events\MapTokenBroadcasted;
use App\Events\MapTokenConditionsExpired;
use App\Models\MapToken;
use App\Models\Region;
use App\Models\Turn;
use App\Models\TurnConfiguration;
use App\Models\User;
use App\Support\Broadcasting\MapTokenPayload;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TurnScheduler
{
    public function __construct(private readonly TurnAiDelegate $aiDelegate)
    {
    }

    public function configure(Region $region, int $turnDurationHours, ?CarbonImmutable $nextTurnAt = null): TurnConfiguration
    {
        $resolvedNextTurn = ($nextTurnAt ?? CarbonImmutable::now('UTC')->addHours($turnDurationHours))->setTimezone('UTC');

        /** @var TurnConfiguration $configuration */
        $configuration = $region->turnConfiguration()->updateOrCreate([], [
            'turn_duration_hours' => $turnDurationHours,
            'next_turn_at' => $resolvedNextTurn,
        ]);

        return $configuration->refresh();
    }

    public function process(Region $region, ?User $actor, ?string $summary = null, bool $useAiFallback = false): Turn
    {
        $configuration = $region->turnConfiguration;

        if ($configuration === null) {
            throw new \InvalidArgumentException('Region is missing a turn configuration.');
        }

        $durationHours = max(1, $configuration->turn_duration_hours);
        $processedAt = CarbonImmutable::now('UTC');
        $scheduledWindowEnd = $configuration->next_turn_at ?? $processedAt;
        $windowEnd = $scheduledWindowEnd->greaterThan($processedAt) ? $scheduledWindowEnd : $processedAt;
        $windowStart = $windowEnd->subHours($durationHours);

        $summaryText = $summary;

        if ($useAiFallback) {
            $summaryText = $summaryText ?? $this->aiDelegate->generateSummary($region, $windowStart, $windowEnd);
        }

        $tokenChanges = [];

        $turn = DB::transaction(function () use (&$tokenChanges, $region, $configuration, $actor, $windowStart, $windowEnd, $processedAt, $summaryText, $useAiFallback, $durationHours): Turn {
            $latestTurn = $region->turns()->lockForUpdate()->orderByDesc('number')->first();
            $nextNumber = $latestTurn?->number + 1 ?? 1;

            /** @var Turn $turn */
            $turn = $region->turns()->create([
                'number' => $nextNumber,
                'window_started_at' => $windowStart,
                'processed_at' => $processedAt,
                'processed_by_id' => $actor?->getAuthIdentifier(),
                'used_ai_fallback' => $useAiFallback,
                'summary' => $summaryText,
            ]);

            $configuration->forceFill([
                'next_turn_at' => $windowEnd->addHours($durationHours),
                'last_processed_at' => $processedAt,
            ])->save();

            $tokenChanges = $this->advanceMapTokenConditionDurations($region);

            return $turn;
        });

        if ($tokenChanges !== []) {
            $tokenIds = array_unique(array_map(static fn (array $change): int => $change['token_id'], $tokenChanges));

            $tokens = MapToken::query()
                ->whereIn('id', $tokenIds)
                ->with('map')
                ->get()
                ->keyBy(fn (MapToken $token): int => (int) $token->id);

            $expiredLookup = [];

            foreach ($tokenChanges as $change) {
                if ($change['expired'] === []) {
                    continue;
                }

                $expiredLookup[$change['token_id']] = $change['expired'];
            }

            foreach ($tokens as $token) {
                event(new MapTokenBroadcasted($token->map, 'updated', MapTokenPayload::from($token)));

                $tokenId = (int) $token->id;

                if ($expiredLookup === [] || ! array_key_exists($tokenId, $expiredLookup)) {
                    continue;
                }

                event(new MapTokenConditionsExpired(
                    $token->map,
                    $tokenId,
                    $token->name,
                    $expiredLookup[$tokenId],
                ));
            }
        }

        return $turn;
    }

    public function scheduleNextTurn(TurnConfiguration $configuration, ?CarbonImmutable $from = null): TurnConfiguration
    {
        $from ??= $configuration->next_turn_at ?? CarbonImmutable::now('UTC');

        $configuration->forceFill([
            'next_turn_at' => $from->addHours(max(1, $configuration->turn_duration_hours)),
        ])->save();

        return $configuration->refresh();
    }

    /**
     * @return array<int, array{token_id: int, expired: array<int, string>}>
     */
    protected function advanceMapTokenConditionDurations(Region $region): array
    {
        $mapIds = $region->maps()->pluck('id');

        if ($mapIds->isEmpty()) {
            return [];
        }

        $changes = [];

        $tokens = MapToken::query()
            ->whereIn('map_id', $mapIds)
            ->lockForUpdate()
            ->get();

        foreach ($tokens as $token) {
            $conditions = $token->status_conditions ?? [];
            $durations = $token->status_condition_durations ?? [];

            if ($conditions === [] || empty($durations)) {
                continue;
            }

            $newConditions = [];
            $newDurations = [];
            $changed = false;
            $expiredConditions = [];

            foreach ($conditions as $condition) {
                if (! array_key_exists($condition, $durations)) {
                    $newConditions[] = $condition;

                    continue;
                }

                $remaining = (int) $durations[$condition] - 1;

                if ($remaining > 0) {
                    $newConditions[] = $condition;
                    $newDurations[$condition] = $remaining;

                    if ($remaining !== (int) $durations[$condition]) {
                        $changed = true;
                    }

                    continue;
                }

                $expiredConditions[] = $condition;
                $changed = true;
            }

            if (! $changed && $newConditions === $conditions && $newDurations === $durations) {
                continue;
            }

            $token->forceFill([
                'status_conditions' => $newConditions,
                'status_condition_durations' => $newDurations,
            ])->save();

            $changes[] = [
                'token_id' => (int) $token->id,
                'expired' => $expiredConditions,
            ];
        }

        return $changes;
    }
}
