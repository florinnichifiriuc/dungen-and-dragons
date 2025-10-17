<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\ConditionTransparencyExport;
use App\Models\ConditionTransparencyWebhook;
use App\Models\ConditionTimerShareConsentLog;

class Group extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'join_code',
        'description',
        'telemetry_opt_out',
        'mentor_briefings_enabled',
        'mentor_briefings_last_generated_at',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'telemetry_opt_out' => 'boolean',
        'mentor_briefings_enabled' => 'boolean',
        'mentor_briefings_last_generated_at' => 'immutable_datetime',
    ];

    public function allowsTelemetry(): bool
    {
        return ! $this->telemetry_opt_out;
    }

    /**
     * Relationship to the user who created the group.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * All membership records associated with the group.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class);
    }

    /**
     * Users that belong to the group.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Worlds curated by the group.
     */
    public function worlds(): HasMany
    {
        return $this->hasMany(World::class);
    }

    /**
     * Regions owned by the group.
     */
    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    /**
     * Campaigns launched by the group.
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Tile templates curated by the group.
     */
    public function tileTemplates(): HasMany
    {
        return $this->hasMany(TileTemplate::class);
    }

    /**
     * Maps owned by the group.
     */
    public function maps(): HasMany
    {
        return $this->hasMany(Map::class);
    }

    public function conditionTimerSummaryShares(): HasMany
    {
        return $this->hasMany(ConditionTimerSummaryShare::class);
    }

    public function conditionTransparencyExports(): HasMany
    {
        return $this->hasMany(ConditionTransparencyExport::class);
    }

    public function conditionTransparencyWebhooks(): HasMany
    {
        return $this->hasMany(ConditionTransparencyWebhook::class);
    }

    public function conditionTimerShareConsentLogs(): HasMany
    {
        return $this->hasMany(ConditionTimerShareConsentLog::class);
    }
}
