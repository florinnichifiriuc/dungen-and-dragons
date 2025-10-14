<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CampaignRoleAssignment extends Model
{
    use HasFactory;

    public const ROLE_GM = 'gm';
    public const ROLE_PLAYER = 'player';
    public const ROLE_OBSERVER = 'observer';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REVOKED = 'revoked';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_id',
        'assignee_type',
        'assignee_id',
        'role',
        'scope',
        'status',
        'assigned_by',
        'accepted_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    /**
     * @return array<int, string>
     */
    public static function roles(): array
    {
        return [
            self::ROLE_GM,
            self::ROLE_PLAYER,
            self::ROLE_OBSERVER,
        ];
    }

    /**
     * Campaign relationship.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Assigned entity.
     */
    public function assignee(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * User that assigned the role.
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
