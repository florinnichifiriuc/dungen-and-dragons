<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConditionTransparencyExport extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'requested_by',
        'format',
        'visibility_mode',
        'filters',
        'status',
        'file_path',
        'failure_reason',
        'retry_attempts',
        'completed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'filters' => 'array',
        'completed_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function markProcessing(): void
    {
        $this->forceFill([
            'status' => self::STATUS_PROCESSING,
            'failure_reason' => null,
        ])->save();
    }

    public function markCompleted(string $filePath): void
    {
        $this->forceFill([
            'status' => self::STATUS_COMPLETED,
            'file_path' => $filePath,
            'completed_at' => now('UTC'),
        ])->save();
    }

    public function markFailed(string $reason): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'completed_at' => now('UTC'),
        ])->save();
    }
}
