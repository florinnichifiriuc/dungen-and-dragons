<?php

namespace App\Services;

use App\Models\Region;
use Carbon\CarbonImmutable;

class TurnAiDelegate
{
    public function generateSummary(Region $region, CarbonImmutable $windowStart, CarbonImmutable $windowEnd): string
    {
        return sprintf(
            'AI chronicler advanced %s from %s to %s with narrative beats pending human embellishment.',
            $region->name,
            $windowStart->toIso8601String(),
            $windowEnd->toIso8601String()
        );
    }
}
