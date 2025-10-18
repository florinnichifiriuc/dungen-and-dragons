<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BugReport extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

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
        'reference',
        'submitted_by',
        'submitted_email',
        'submitted_name',
        'group_id',
        'context_type',
        'context_identifier',
        'status',
        'priority',
        'summary',
        'description',
        'environment',
        'ai_context',
        'tags',
        'assigned_to',
        'acknowledged_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'environment' => 'array',
        'ai_context' => 'array',
        'tags' => 'array',
        'acknowledged_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $report): void {
            if (! $report->getKey()) {
                $report->setAttribute($report->getKeyName(), (string) Str::uuid());
            }

            if (! $report->reference) {
                $report->reference = 'BR-'.Str::upper(Str::random(6));
            }
        });
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(BugReportUpdate::class);
    }
}
