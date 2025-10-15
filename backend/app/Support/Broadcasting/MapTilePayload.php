<?php

namespace App\Support\Broadcasting;

use App\Models\MapTile;

class MapTilePayload
{
    /**
     * Transform a map tile for broadcasting to connected clients.
     *
     * @return array<string, mixed>
     */
    public static function from(MapTile $tile): array
    {
        $tile->loadMissing('tileTemplate:id,name,terrain_type,movement_cost,defense_bonus');

        return [
            'id' => (int) $tile->id,
            'q' => (int) $tile->q,
            'r' => (int) $tile->r,
            'elevation' => (int) ($tile->elevation ?? 0),
            'locked' => (bool) $tile->locked,
            'variant' => $tile->variant,
            'template' => [
                'id' => (int) optional($tile->tileTemplate)->id,
                'name' => optional($tile->tileTemplate)->name,
                'terrain_type' => optional($tile->tileTemplate)->terrain_type,
                'movement_cost' => (int) optional($tile->tileTemplate)->movement_cost,
                'defense_bonus' => (int) optional($tile->tileTemplate)->defense_bonus,
            ],
        ];
    }
}
