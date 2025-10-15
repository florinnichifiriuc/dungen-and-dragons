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

    public const CONDITIONS = [
        'blinded',
        'charmed',
        'deafened',
        'frightened',
        'grappled',
        'incapacitated',
        'invisible',
        'paralyzed',
        'petrified',
        'poisoned',
        'prone',
        'restrained',
        'stunned',
        'unconscious',
        'exhaustion',
    ];

    public const MAX_CONDITION_DURATION = 20;

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
        'status_conditions',
        'status_condition_durations',
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
        'status_conditions' => 'array',
        'status_condition_durations' => 'array',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'faction' => self::FACTION_NEUTRAL,
        'status_conditions' => [],
        'status_condition_durations' => [],
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
