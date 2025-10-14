<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiceRoll extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_id',
        'campaign_session_id',
        'roller_id',
        'expression',
        'result_breakdown',
        'result_total',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'result_breakdown' => 'array',
        'result_total' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CampaignSession::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function roller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'roller_id');
    }
}
