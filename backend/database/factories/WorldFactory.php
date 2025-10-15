<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\World;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<World>
 */
class WorldFactory extends Factory
{
    protected $model = World::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'summary' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'default_turn_duration_hours' => $this->faker->numberBetween(6, 72),
        ];
    }
}
