<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\SessionRecap;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionRecap>
 */
class SessionRecapFactory extends Factory
{
    protected $model = SessionRecap::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'campaign_session_id' => CampaignSession::factory(),
            'author_id' => User::factory(),
            'title' => $this->faker->optional()->sentence(4),
            'body' => $this->faker->paragraphs(2, true),
        ];
    }
}
