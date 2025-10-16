<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\User;

class NotificationPreference extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'channel_in_app',
        'channel_push',
        'channel_email',
        'quiet_hours_start',
        'quiet_hours_end',
        'digest_delivery',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'channel_in_app' => 'boolean',
        'channel_push' => 'boolean',
        'channel_email' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(User $user): self
    {
        return $user->notificationPreference()->firstOrCreate([], [
            'channel_in_app' => true,
            'channel_push' => false,
            'channel_email' => true,
            'digest_delivery' => 'off',
        ]);
    }
}
