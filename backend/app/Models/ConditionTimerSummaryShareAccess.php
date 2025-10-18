<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConditionTimerSummaryShareAccess extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'condition_timer_summary_share_id',
        'event_type',
        'occurred_at',
        'ip_hash',
        'user_agent_hash',
        'user_id',
        'quiet_hour_suppressed',
        'metadata',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'occurred_at' => 'immutable_datetime',
        'metadata' => 'array',
        'quiet_hour_suppressed' => 'boolean',
    ];

    public function scopeForShare(Builder $query, ConditionTimerSummaryShare $share): Builder
    {
        return $query->where('condition_timer_summary_share_id', $share->id);
    }

    public function scopeForEvent(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeBetween(Builder $query, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return $query->whereBetween('occurred_at', [$start, $end]);
    }

    public function scopeQuietHours(Builder $query): Builder
    {
        return $query->where('quiet_hour_suppressed', true);
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(ConditionTimerSummaryShare::class, 'condition_timer_summary_share_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
