<?php

namespace Database\Factories;

use App\Models\Region;
use App\Models\Turn;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Turn>
 */
class TurnFactory extends Factory
{
    protected $model = Turn::class;

    public function definition(): array
    {
        $windowStart = CarbonImmutable::now('UTC')->subHours(24);

        return [
            'region_id' => Region::factory(),
            'number' => 1,
            'window_started_at' => $windowStart,
            'processed_at' => $windowStart->addHours(24),
            'processed_by_id' => User::factory(),
            'used_ai_fallback' => false,
            'summary' => $this->faker->paragraph(),
        ];
    }
}
