<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignQuest extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const PRIORITY_CRITICAL = 'critical';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_STANDARD = 'standard';
    public const PRIORITY_LOW = 'low';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_id',
        'region_id',
        'created_by_id',
        'title',
        'summary',
        'details',
        'status',
        'priority',
        'target_turn_number',
        'starts_at',
        'completed_at',
        'archived_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'immutable_datetime',
        'completed_at' => 'immutable_datetime',
        'archived_at' => 'immutable_datetime',
    ];

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PLANNED,
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function priorities(): array
    {
        return [
            self::PRIORITY_CRITICAL,
            self::PRIORITY_HIGH,
            self::PRIORITY_STANDARD,
            self::PRIORITY_LOW,
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(CampaignQuestUpdate::class, 'quest_id');
    }
}
