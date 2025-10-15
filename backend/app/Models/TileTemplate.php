<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TileTemplate extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'world_id',
        'created_by',
        'key',
        'name',
        'terrain_type',
        'movement_cost',
        'defense_bonus',
        'image_path',
        'edge_profile',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'edge_profile' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mapTiles(): HasMany
    {
        return $this->hasMany(MapTile::class);
    }
}
