<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PLANNING = 'planning';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'region_id',
        'created_by',
        'title',
        'slug',
        'synopsis',
        'status',
        'default_timezone',
        'start_date',
        'end_date',
        'turn_hours',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'turn_hours' => 'integer',
    ];

    /**
     * All supported campaign statuses.
     *
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PLANNING,
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_ARCHIVED,
        ];
    }

    /**
     * Owning group relationship.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Optional region relationship.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Campaign creator relationship.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Invitations linked to the campaign.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(CampaignInvitation::class);
    }

    /**
     * Role assignments for the campaign.
     */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(CampaignRoleAssignment::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CampaignSession::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CampaignTask::class);
    }

    public function entities(): HasMany
    {
        return $this->hasMany(CampaignEntity::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function quests(): HasMany
    {
        return $this->hasMany(CampaignQuest::class);
    }
}
