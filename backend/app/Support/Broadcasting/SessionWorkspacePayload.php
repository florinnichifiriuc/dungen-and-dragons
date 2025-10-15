<?php

namespace App\Support\Broadcasting;

use App\Models\DiceRoll;
use App\Models\InitiativeEntry;
use App\Models\SessionNote;

class SessionWorkspacePayload
{
    /**
     * Transform a session note for broadcasting to workspace clients.
     *
     * @return array<string, mixed>
     */
    public static function note(SessionNote $note): array
    {
        $note->loadMissing('author:id,name');

        return [
            'id' => (int) $note->id,
            'content' => $note->content,
            'visibility' => $note->visibility,
            'is_pinned' => (bool) $note->is_pinned,
            'author' => [
                'id' => (int) optional($note->author)->id,
                'name' => optional($note->author)->name,
            ],
            'created_at' => $note->created_at?->toIso8601String(),
            'updated_at' => $note->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Transform a dice roll for broadcasting.
     *
     * @return array<string, mixed>
     */
    public static function diceRoll(DiceRoll $roll): array
    {
        $roll->loadMissing('roller:id,name');

        return [
            'id' => (int) $roll->id,
            'expression' => $roll->expression,
            'result_total' => (int) $roll->result_total,
            'result_breakdown' => $roll->result_breakdown,
            'roller' => [
                'id' => (int) optional($roll->roller)->id,
                'name' => optional($roll->roller)->name,
            ],
            'created_at' => $roll->created_at?->toIso8601String(),
        ];
    }

    /**
     * Transform an initiative entry for broadcasting.
     *
     * @return array<string, mixed>
     */
    public static function initiativeEntry(InitiativeEntry $entry): array
    {
        return [
            'id' => (int) $entry->id,
            'name' => $entry->name,
            'dexterity_mod' => (int) $entry->dexterity_mod,
            'initiative' => (int) $entry->initiative,
            'is_current' => (bool) $entry->is_current,
            'order_index' => (int) $entry->order_index,
        ];
    }
}
