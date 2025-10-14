<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\Turn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignSession>
 */
class CampaignSessionFactory extends Factory
{
    protected $model = CampaignSession::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'turn_id' => null,
            'created_by' => User::factory(),
            'title' => ucfirst($this->faker->words(3, true)),
            'agenda' => $this->faker->paragraph(),
            'session_date' => now()->addDays($this->faker->numberBetween(0, 10)),
            'duration_minutes' => $this->faker->numberBetween(60, 240),
            'location' => $this->faker->city(),
            'summary' => $this->faker->paragraph(),
            'recording_url' => $this->faker->url(),
        ];
    }

    public function forTurn(): self
    {
        return $this->state(fn () => [
            'turn_id' => Turn::factory(),
        ]);
    }
}
