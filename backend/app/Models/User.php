<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'account_role',
        'is_support_admin',
        'locale',
        'timezone',
        'theme',
        'high_contrast',
        'font_scale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'high_contrast' => 'boolean',
            'font_scale' => 'integer',
            'account_role' => 'string',
            'is_support_admin' => 'boolean',
        ];
    }

    /**
     * Supported global account roles.
     *
     * @return array<int, string>
     */
    public static function accountRoles(): array
    {
        return ['player', 'guide', 'admin'];
    }

    public function isAdmin(): bool
    {
        return $this->account_role === 'admin' || $this->is_support_admin;
    }

    /**
     * Membership records associated with the user.
     */
    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class);
    }

    /**
     * Groups the user belongs to.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Groups created by the user.
     */
    public function ownedGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'created_by');
    }

    /**
     * Regions where the user is assigned as dungeon master.
     */
    public function dungeonMasterRegions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Region::class,
            GroupMembership::class,
            'user_id',
            'dungeon_master_id',
            'id',
            'group_id'
        );
    }

    /**
     * Campaigns created by the user.
     */
    public function ownedCampaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    /**
     * Campaign role assignments for the user.
     */
    public function campaignRoles(): MorphMany
    {
        return $this->morphMany(CampaignRoleAssignment::class, 'assignee');
    }

    public function sessionAttendances(): HasMany
    {
        return $this->hasMany(SessionAttendance::class);
    }

    public function sessionRecaps(): HasMany
    {
        return $this->hasMany(SessionRecap::class, 'author_id');
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }
}
