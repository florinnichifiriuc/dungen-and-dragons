<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        $title = Str::title($this->faker->unique()->words(3, true));

        return [
            'group_id' => Group::factory(),
            'region_id' => null,
            'created_by' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->lexify('???'),
            'synopsis' => $this->faker->paragraph(),
            'status' => Campaign::STATUS_PLANNING,
            'default_timezone' => 'UTC',
            'start_date' => now()->toDateString(),
            'turn_hours' => 24,
        ];
    }
}
