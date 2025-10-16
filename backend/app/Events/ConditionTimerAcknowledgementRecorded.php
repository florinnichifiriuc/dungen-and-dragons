<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConditionTimerAcknowledgementRecorded implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $groupId,
        public int $tokenId,
        public string $conditionKey,
        public string $summaryGeneratedAt,
        public int $acknowledgedCount,
        public int $actorId
    ) {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("groups.{$this->groupId}.condition-timers")];
    }

    public function broadcastAs(): string
    {
        return 'condition-timer-acknowledgement.recorded';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'token_id' => $this->tokenId,
            'condition_key' => $this->conditionKey,
            'summary_generated_at' => $this->summaryGeneratedAt,
            'acknowledged_count' => $this->acknowledgedCount,
            'actor_id' => $this->actorId,
        ];
    }
}
