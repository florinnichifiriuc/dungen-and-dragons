<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Region extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'world_id',
        'dungeon_master_id',
        'ai_controlled',
        'name',
        'summary',
        'description',
        'ai_delegate_summary',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'ai_controlled' => 'boolean',
    ];

    /**
     * Parent world for this region.
     */
    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    /**
     * Owning group.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Assigned dungeon master.
     */
    public function dungeonMaster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dungeon_master_id');
    }

    /**
     * Turn configuration for this region.
     */
    public function turnConfiguration(): HasOne
    {
        return $this->hasOne(TurnConfiguration::class);
    }

    public function turns(): HasMany
    {
        return $this->hasMany(Turn::class);
    }

    public function maps(): HasMany
    {
        return $this->hasMany(Map::class);
    }

    public function aiRequests(): MorphMany
    {
        return $this->morphMany(AiRequest::class, 'context');
    }
}
