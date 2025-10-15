<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CampaignEntity extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_CHARACTER = 'character';
    public const TYPE_NPC = 'npc';
    public const TYPE_MONSTER = 'monster';
    public const TYPE_ITEM = 'item';
    public const TYPE_LOCATION = 'location';

    public const VISIBILITY_GM = 'gm';
    public const VISIBILITY_PLAYERS = 'players';
    public const VISIBILITY_PUBLIC = 'public';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_id',
        'group_id',
        'owner_id',
        'entity_type',
        'name',
        'slug',
        'alias',
        'pronunciation',
        'visibility',
        'ai_controlled',
        'initiative_default',
        'stats',
        'description',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'ai_controlled' => 'boolean',
        'initiative_default' => 'integer',
        'stats' => 'array',
    ];

    /**
     * All supported entity archetypes.
     *
     * @return array<int, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_CHARACTER,
            self::TYPE_NPC,
            self::TYPE_MONSTER,
            self::TYPE_ITEM,
            self::TYPE_LOCATION,
        ];
    }

    /**
     * Supported visibility levels.
     *
     * @return array<int, string>
     */
    public static function visibilities(): array
    {
        return [
            self::VISIBILITY_GM,
            self::VISIBILITY_PLAYERS,
            self::VISIBILITY_PUBLIC,
        ];
    }

    /**
     * Scope entities to a given campaign.
     */
    public function scopeForCampaign(Builder $query, Campaign $campaign): Builder
    {
        return $query->where('campaign_id', $campaign->getKey());
    }

    /**
     * Ensure a campaign-scoped slug is generated from the provided name.
     */
    public function generateSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::query()
            ->where('campaign_id', $this->campaign_id)
            ->where('slug', $slug)
            ->when($this->exists, fn (Builder $builder) => $builder->whereKeyNot($this->getKey()))
            ->exists()) {
            $slug = Str::slug($baseSlug.'-'.$counter++);
        }

        return $slug;
    }

    /**
     * Owning campaign relationship.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Optional associated group.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Optional owner reference.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Attached tags.
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')->withTimestamps();
    }
}
