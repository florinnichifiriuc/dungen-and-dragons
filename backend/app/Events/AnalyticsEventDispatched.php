<?php

namespace App\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalyticsEventDispatched
{
    use Dispatchable;
    use SerializesModels;

    public readonly CarbonImmutable $recordedAt;

    public function __construct(
        public readonly string $key,
        public readonly array $payload = [],
        public readonly ?int $userId = null,
        public readonly ?int $groupId = null,
        ?CarbonImmutable $recordedAt = null
    ) {
        $this->recordedAt = $recordedAt?->setTimezone('UTC') ?? CarbonImmutable::now('UTC');
    }
}
