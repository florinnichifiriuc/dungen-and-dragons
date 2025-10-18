<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class AiRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const MODERATION_PENDING = 'pending';
    public const MODERATION_APPROVED = 'approved';
    public const MODERATION_REJECTED = 'rejected';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'request_type',
        'context_type',
        'context_id',
        'meta',
        'prompt',
        'response_text',
        'response_payload',
        'status',
        'provider',
        'model',
        'created_by',
        'moderation_status',
        'moderation_notes',
        'moderated_by',
        'moderated_at',
        'completed_at',
        'failed_at',
        'error_message',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
        'response_payload' => 'array',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'moderated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            if (! $request->getKey()) {
                $request->setAttribute($request->getKeyName(), (string) Str::uuid());
            }
        });
    }

    public function context(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function markCompleted(string $responseText, array $payload = []): void
    {
        $this->forceFill([
            'status' => self::STATUS_COMPLETED,
            'response_text' => $responseText,
            'response_payload' => $payload,
            'completed_at' => now('UTC'),
            'failed_at' => null,
            'error_message' => null,
        ])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'failed_at' => now('UTC'),
            'error_message' => $message,
        ])->save();
    }

    public function markModerationPending(?string $notes = null): void
    {
        $this->forceFill([
            'moderation_status' => self::MODERATION_PENDING,
            'moderation_notes' => $notes,
            'moderated_by' => null,
            'moderated_at' => null,
        ])->save();
    }

    public function markModerationApproved(?int $moderatorId, ?string $notes = null): void
    {
        $this->forceFill([
            'moderation_status' => self::MODERATION_APPROVED,
            'moderation_notes' => $notes,
            'moderated_by' => $moderatorId,
            'moderated_at' => now('UTC'),
        ])->save();
    }

    public function markModerationRejected(?int $moderatorId, ?string $notes = null): void
    {
        $this->forceFill([
            'moderation_status' => self::MODERATION_REJECTED,
            'moderation_notes' => $notes,
            'moderated_by' => $moderatorId,
            'moderated_at' => now('UTC'),
        ])->save();
    }
}
