<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MapToken extends Model
{
    use HasFactory;

    public const FACTION_ALLIED = 'allied';

    public const FACTION_HOSTILE = 'hostile';

    public const FACTION_NEUTRAL = 'neutral';

    public const FACTION_HAZARD = 'hazard';

    public const FACTIONS = [
        self::FACTION_ALLIED,
        self::FACTION_HOSTILE,
        self::FACTION_NEUTRAL,
        self::FACTION_HAZARD,
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'map_id',
        'entity_type',
        'entity_id',
        'name',
        'x',
        'y',
        'color',
        'size',
        'faction',
        'initiative',
        'status_effects',
        'hit_points',
        'temporary_hit_points',
        'max_hit_points',
        'z_index',
        'hidden',
        'gm_note',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'hidden' => 'boolean',
        'initiative' => 'integer',
        'z_index' => 'integer',
        'hit_points' => 'integer',
        'temporary_hit_points' => 'integer',
        'max_hit_points' => 'integer',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'faction' => self::FACTION_NEUTRAL,
    ];

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
