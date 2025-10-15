<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignQuestUpdate extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'quest_id',
        'created_by_id',
        'summary',
        'details',
        'recorded_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'recorded_at' => 'immutable_datetime',
    ];

    public function quest(): BelongsTo
    {
        return $this->belongsTo(CampaignQuest::class, 'quest_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
