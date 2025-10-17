<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConditionTransparencyWebhook extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'url',
        'secret',
        'active',
        'call_count',
        'consecutive_failures',
        'last_triggered_at',
        'last_failed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'last_triggered_at' => 'immutable_datetime',
        'last_failed_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function rotateSecret(): void
    {
        $this->forceFill([
            'secret' => bin2hex(random_bytes(16)),
            'consecutive_failures' => 0,
        ])->save();
    }
}
