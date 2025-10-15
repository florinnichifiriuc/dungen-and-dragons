<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionAttendance extends Model
{
    use HasFactory;

    public const STATUS_YES = 'yes';
    public const STATUS_MAYBE = 'maybe';
    public const STATUS_NO = 'no';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_session_id',
        'user_id',
        'status',
        'note',
        'responded_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'responded_at' => 'datetime',
    ];

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_YES,
            self::STATUS_MAYBE,
            self::STATUS_NO,
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CampaignSession::class, 'campaign_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
