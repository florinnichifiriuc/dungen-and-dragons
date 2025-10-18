<?php

namespace App\Models;

use App\Models\ConditionTimerSummaryShareAccess;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConditionTimerSummaryShare extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'created_by',
        'token',
        'expires_at',
        'visibility_mode',
        'preset_key',
        'consent_snapshot',
        'access_count',
        'last_accessed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
        'deleted_at' => 'immutable_datetime',
        'consent_snapshot' => 'array',
        'last_accessed_at' => 'immutable_datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        $now = CarbonImmutable::now('UTC');

        return $query
            ->whereNull('deleted_at')
            ->where(function (Builder $builder) use ($now) {
                $builder
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            });
    }

    public function scopeExpired(Builder $query): Builder
    {
        $now = CarbonImmutable::now('UTC');

        return $query
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now);
    }

    public function scopeEvergreen(Builder $query): Builder
    {
        return $query->whereNull('expires_at');
    }

    public function isEvergreen(): bool
    {
        return $this->expires_at === null;
    }

    public function expiresWithinHours(int $hours): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        $threshold = CarbonImmutable::now('UTC')->addHours($hours);

        return $this->expires_at->lessThanOrEqualTo($threshold) && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(ConditionTimerSummaryShareAccess::class);
    }
}
