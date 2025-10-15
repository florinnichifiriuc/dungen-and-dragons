<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionReward extends Model
{
    use HasFactory;

    public const TYPE_LOOT = 'loot';
    public const TYPE_XP = 'experience';
    public const TYPE_BOON = 'boon';
    public const TYPE_NOTE = 'note';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_id',
        'campaign_session_id',
        'recorded_by',
        'reward_type',
        'title',
        'quantity',
        'awarded_to',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_LOOT,
            self::TYPE_XP,
            self::TYPE_BOON,
            self::TYPE_NOTE,
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CampaignSession::class, 'campaign_session_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
