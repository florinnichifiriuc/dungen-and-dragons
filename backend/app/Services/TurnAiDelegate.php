<?php

namespace App\Services;

use App\Models\Region;
use Carbon\CarbonImmutable;

class TurnAiDelegate
{
    public function __construct(private readonly AiContentService $content)
    {
    }

    public function generateSummary(Region $region, CarbonImmutable $windowStart, CarbonImmutable $windowEnd): string
    {
        return $this->content->summarizeTurn($region, $windowStart, $windowEnd);
    }
}
