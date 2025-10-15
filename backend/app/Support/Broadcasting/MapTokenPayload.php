<?php

namespace App\Support\Broadcasting;

use App\Models\MapToken;

class MapTokenPayload
{
    /**
     * Transform a map token for broadcasting to connected clients.
     *
     * @return array<string, mixed>
     */
    public static function from(MapToken $token): array
    {
        return [
            'id' => (int) $token->id,
            'name' => $token->name,
            'x' => (int) $token->x,
            'y' => (int) $token->y,
            'color' => $token->color,
            'size' => $token->size,
            'faction' => $token->faction,
            'initiative' => $token->initiative,
            'status_effects' => $token->status_effects,
            'hit_points' => $token->hit_points,
            'temporary_hit_points' => $token->temporary_hit_points,
            'max_hit_points' => $token->max_hit_points,
            'z_index' => $token->z_index,
            'hidden' => (bool) $token->hidden,
            'gm_note' => $token->gm_note,
        ];
    }
}
