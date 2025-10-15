<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignTask extends Model
{
    use HasFactory;

    public const STATUS_BACKLOG = 'backlog';
    public const STATUS_READY = 'ready';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVIEW = 'review';
    public const STATUS_COMPLETED = 'completed';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_id',
        'created_by_id',
        'assigned_user_id',
        'assigned_group_id',
        'title',
        'description',
        'status',
        'position',
        'due_turn_number',
        'due_at',
        'completed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'due_at' => 'immutable_datetime',
        'completed_at' => 'immutable_datetime',
    ];

    /**
     * All supported task statuses for the Kanban board.
     *
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_BACKLOG,
            self::STATUS_READY,
            self::STATUS_ACTIVE,
            self::STATUS_REVIEW,
            self::STATUS_COMPLETED,
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function assigneeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function assigneeGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'assigned_group_id');
    }
}
