<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class World extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'name',
        'summary',
        'description',
        'default_turn_duration_hours',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Regions that belong to this world.
     */
    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function tileTemplates(): HasMany
    {
        return $this->hasMany(TileTemplate::class);
    }
}
