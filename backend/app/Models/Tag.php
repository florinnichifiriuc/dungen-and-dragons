<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_id',
        'label',
        'slug',
        'color',
    ];

    /**
     * Campaign relationship when scoped.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Associated campaign entities.
     */
    public function entities(): MorphToMany
    {
        return $this->morphedByMany(CampaignEntity::class, 'taggable')->withTimestamps();
    }

    /**
     * Generate a slug within the campaign scope.
     */
    public static function slugFor(string $label): string
    {
        $slug = Str::slug($label);

        return $slug !== '' ? $slug : Str::slug(Str::random(6));
    }
}
