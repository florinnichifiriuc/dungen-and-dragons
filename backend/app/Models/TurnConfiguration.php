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
        'last_processed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'next_turn_at' => 'immutable_datetime',
        'last_processed_at' => 'immutable_datetime',
    ];

    /**
     * Region relationship.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function isDue(?\Carbon\CarbonImmutable $reference = null): bool
    {
        $reference ??= \Carbon\CarbonImmutable::now('UTC');

        if ($this->next_turn_at === null) {
            return true;
        }

        return $this->next_turn_at->lessThanOrEqualTo($reference);
    }
}
