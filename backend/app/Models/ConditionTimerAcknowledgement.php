<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConditionTimerAcknowledgement extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'map_token_id',
        'user_id',
        'condition_key',
        'summary_generated_at',
        'acknowledged_at',
        'queued_at',
        'source',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'summary_generated_at' => 'immutable_datetime',
        'acknowledged_at' => 'immutable_datetime',
        'queued_at' => 'immutable_datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(MapToken::class, 'map_token_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
