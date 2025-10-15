<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\SessionReward;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionReward>
 */
class SessionRewardFactory extends Factory
{
    protected $model = SessionReward::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'campaign_session_id' => CampaignSession::factory(),
            'recorded_by' => User::factory(),
            'reward_type' => $this->faker->randomElement(SessionReward::types()),
            'title' => $this->faker->sentence(3),
            'quantity' => $this->faker->optional()->numberBetween(1, 500),
            'awarded_to' => $this->faker->optional()->name(),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
