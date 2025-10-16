<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        $slugBase = Str::slug($name);

        return [
            'name' => Str::title($name),
            'slug' => $slugBase.'-'.$this->faker->unique()->lexify('???'),
            'join_code' => Str::upper($this->faker->unique()->lexify('??????')),
            'description' => $this->faker->optional()->sentence(),
            'telemetry_opt_out' => false,
            'created_by' => User::factory(),
        ];
    }
}
