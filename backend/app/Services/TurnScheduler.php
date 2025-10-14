<?php

namespace App\Services;

use App\Models\Region;
use App\Models\TurnConfiguration;
use Carbon\CarbonImmutable;

class TurnScheduler
{
    public function configure(Region $region, int $turnDurationHours, ?CarbonImmutable $nextTurnAt = null): TurnConfiguration
    {
        return $region->turnConfiguration()->updateOrCreate([], [
            'turn_duration_hours' => $turnDurationHours,
            'next_turn_at' => $nextTurnAt,
        ]);
    }

    public function scheduleNextTurn(TurnConfiguration $configuration): void
    {
        // Stub: actual scheduling logic will be implemented in the dedicated turn processor task.
    }
}
