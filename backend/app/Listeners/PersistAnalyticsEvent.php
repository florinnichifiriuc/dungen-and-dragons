<?php

namespace App\Listeners;

use App\Events\AnalyticsEventDispatched;
use App\Models\AnalyticsEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class PersistAnalyticsEvent implements ShouldQueue
{
    public function handle(AnalyticsEventDispatched $event): void
    {
        AnalyticsEvent::create([
            'key' => $event->key,
            'user_id' => $event->userId,
            'group_id' => $event->groupId,
            'payload' => $event->payload,
            'recorded_at' => $event->recordedAt,
        ]);
    }
}
