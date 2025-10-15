<?php

namespace App\Events;

use App\Models\Map;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MapTileBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $tile
     */
    public function __construct(
        public Map $map,
        public string $action,
        public array $tile
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
        return "map-tile.{$this->action}";
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tile' => $this->tile,
        ];
    }
}
