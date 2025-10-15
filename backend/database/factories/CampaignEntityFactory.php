<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignEntity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CampaignEntity>
 */
class CampaignEntityFactory extends Factory
{
    protected $model = CampaignEntity::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'campaign_id' => Campaign::factory(),
            'entity_type' => $this->faker->randomElement(CampaignEntity::types()),
            'name' => Str::title($name),
            'slug' => Str::slug($name.'-'.$this->faker->unique()->numberBetween(10, 9999)),
            'alias' => $this->faker->optional()->words(2, true),
            'pronunciation' => $this->faker->optional()->lexify('????-????'),
            'visibility' => $this->faker->randomElement(CampaignEntity::visibilities()),
            'ai_controlled' => $this->faker->boolean(20),
            'initiative_default' => $this->faker->optional()->numberBetween(1, 35),
            'description' => $this->faker->paragraph(),
            'stats' => [],
        ];
    }
}
