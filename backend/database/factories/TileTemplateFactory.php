<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\TileTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TileTemplate>
 */
class TileTemplateFactory extends Factory
{
    protected $model = TileTemplate::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'world_id' => null,
            'created_by' => User::factory(),
            'key' => $this->faker->unique()->lexify('tile-????'),
            'name' => $this->faker->words(2, true),
            'terrain_type' => $this->faker->randomElement(['grassland', 'forest', 'mountain', 'water']),
            'movement_cost' => $this->faker->numberBetween(1, 6),
            'defense_bonus' => $this->faker->numberBetween(0, 4),
            'image_path' => null,
            'edge_profile' => ['north' => 'open', 'south' => 'open'],
        ];
    }
}
