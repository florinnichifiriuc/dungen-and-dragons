<?php

namespace App\Events;

use App\Models\Map;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MapTokenConditionsExpired implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<int, string>  $conditions
     */
    public function __construct(
        public Map $map,
        public int $tokenId,
        public string $tokenName,
        public array $conditions,
    ) {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("groups.{$this->map->group_id}.maps.{$this->map->id}")];
    }

    public function broadcastAs(): string
    {
        return 'map-token.conditions-expired';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'token' => [
                'id' => $this->tokenId,
                'name' => $this->tokenName,
            ],
            'conditions' => $this->conditions,
        ];
    }
}
