<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Map extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'region_id',
        'title',
        'base_layer',
        'orientation',
        'width',
        'height',
        'gm_only',
        'fog_data',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'gm_only' => 'boolean',
        'fog_data' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function tiles(): HasMany
    {
        return $this->hasMany(MapTile::class);
    }
}
