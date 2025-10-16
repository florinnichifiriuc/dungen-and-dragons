<?php

namespace App\Services;

use App\Events\AnalyticsEventDispatched;
use App\Models\AnalyticsEvent;
use App\Models\Group;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;

class AnalyticsRecorder
{
    public function __construct(private readonly Dispatcher $events)
    {
    }

    public function record(
        string $key,
        array $payload = [],
        ?User $actor = null,
        ?Group $group = null,
        ?CarbonImmutable $occurredAt = null
    ): void {
        if ($group !== null && ! $this->groupAllowsTelemetry($group)) {
            return;
        }

        $recordedAt = $occurredAt ?? CarbonImmutable::now('UTC');

        AnalyticsEvent::create([
            'key' => $key,
            'user_id' => $actor?->getAuthIdentifier(),
            'group_id' => $group?->getKey(),
            'payload' => $payload,
            'recorded_at' => $recordedAt,
        ]);

        $this->events->dispatch(new AnalyticsEventDispatched(
            key: $key,
            payload: $payload,
            userId: $actor?->getAuthIdentifier(),
            groupId: $group?->getKey(),
            recordedAt: $recordedAt,
        ));
    }

    protected function groupAllowsTelemetry(Group $group): bool
    {
        return ! (bool) $group->telemetry_opt_out;
    }
}
