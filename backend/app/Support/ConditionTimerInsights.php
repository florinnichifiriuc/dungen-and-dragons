<?php

namespace App\Support;

class ConditionTimerInsights
{
    public static function urgency(?int $rounds): string
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
}
