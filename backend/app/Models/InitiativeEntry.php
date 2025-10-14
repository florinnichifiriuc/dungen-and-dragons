<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InitiativeEntry extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_session_id',
        'name',
        'entity_type',
        'entity_id',
        'dexterity_mod',
        'initiative',
        'is_current',
        'order_index',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'dexterity_mod' => 'integer',
        'initiative' => 'integer',
        'order_index' => 'integer',
        'is_current' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CampaignSession::class);
    }
}
