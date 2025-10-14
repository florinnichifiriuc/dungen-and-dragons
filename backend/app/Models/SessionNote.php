<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionNote extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const VISIBILITY_GM = 'gm';
    public const VISIBILITY_PLAYERS = 'players';
    public const VISIBILITY_PUBLIC = 'public';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_id',
        'campaign_session_id',
        'author_id',
        'visibility',
        'is_pinned',
        'content',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public static function visibilities(): array
    {
        return [
            self::VISIBILITY_GM,
            self::VISIBILITY_PLAYERS,
            self::VISIBILITY_PUBLIC,
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CampaignSession::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
