<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMembership extends Model
{
    use HasFactory;

    public const ROLE_OWNER = 'owner';
    public const ROLE_DUNGEON_MASTER = 'dungeon-master';
    public const ROLE_PLAYER = 'player';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'user_id',
        'role',
    ];

    /**
     * Group relationship.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * User relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to list supported roles.
     *
     * @return array<int, string>
     */
    public static function roles(): array
    {
        return [
            self::ROLE_OWNER,
            self::ROLE_DUNGEON_MASTER,
            self::ROLE_PLAYER,
        ];
    }
}
