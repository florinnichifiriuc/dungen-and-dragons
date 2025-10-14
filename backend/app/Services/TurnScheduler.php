<?php

namespace App\Services;

use App\Models\Region;
use App\Models\Turn;
use App\Models\TurnConfiguration;
use App\Models\User;
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

        return DB::transaction(function () use ($region, $configuration, $actor, $windowStart, $windowEnd, $processedAt, $summaryText, $useAiFallback, $durationHours): Turn {
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

            return $turn;
        });
    }

    public function scheduleNextTurn(TurnConfiguration $configuration, ?CarbonImmutable $from = null): TurnConfiguration
    {
        $from ??= $configuration->next_turn_at ?? CarbonImmutable::now('UTC');

        $configuration->forceFill([
            'next_turn_at' => $from->addHours(max(1, $configuration->turn_duration_hours)),
        ])->save();

        return $configuration->refresh();
    }
}
