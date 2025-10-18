<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugReportUpdate extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'bug_report_id',
        'user_id',
        'type',
        'payload',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(BugReport::class, 'bug_report_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
