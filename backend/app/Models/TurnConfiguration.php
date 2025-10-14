<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurnConfiguration extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'region_id',
        'turn_duration_hours',
        'next_turn_at',
    ];

    /**
     * Region relationship.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
