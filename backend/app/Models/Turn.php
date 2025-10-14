<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Turn extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'region_id',
        'number',
        'window_started_at',
        'processed_at',
        'processed_by_id',
        'used_ai_fallback',
        'summary',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'window_started_at' => 'immutable_datetime',
        'processed_at' => 'immutable_datetime',
        'used_ai_fallback' => 'boolean',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }
}
