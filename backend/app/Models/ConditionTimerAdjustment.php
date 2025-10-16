<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConditionTimerAdjustment extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'map_token_id',
        'condition_key',
        'previous_rounds',
        'new_rounds',
        'delta',
        'reason',
        'context',
        'actor_id',
        'actor_role',
        'recorded_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
        'recorded_at' => 'immutable_datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(MapToken::class, 'map_token_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
