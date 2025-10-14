<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Region extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'dungeon_master_id',
        'name',
        'summary',
        'description',
    ];

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
}
