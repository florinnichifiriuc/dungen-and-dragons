<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapTile extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'map_id',
        'tile_template_id',
        'q',
        'r',
        'orientation',
        'elevation',
        'variant',
        'locked',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'variant' => 'array',
        'locked' => 'boolean',
    ];

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }

    public function tileTemplate(): BelongsTo
    {
        return $this->belongsTo(TileTemplate::class);
    }
}
