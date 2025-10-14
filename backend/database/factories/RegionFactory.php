<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    protected $model = Region::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'name' => $this->faker->unique()->words(2, true),
            'summary' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
        ];
    }
}
