<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignSession extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_id',
        'turn_id',
        'created_by',
        'title',
        'agenda',
        'session_date',
        'duration_minutes',
        'location',
        'summary',
        'recording_url',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'session_date' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function turn(): BelongsTo
    {
        return $this->belongsTo(Turn::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(SessionNote::class);
    }

    public function diceRolls(): HasMany
    {
        return $this->hasMany(DiceRoll::class);
    }

    public function initiativeEntries(): HasMany
    {
        return $this->hasMany(InitiativeEntry::class)->orderBy('order_index');
    }

    public function aiRequests(): MorphMany
    {
        return $this->morphMany(AiRequest::class, 'context');
    }
}
