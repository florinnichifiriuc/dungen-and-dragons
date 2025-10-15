<?php

namespace Database\Factories;

use App\Models\CampaignSession;
use App\Models\SessionAttendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionAttendance>
 */
class SessionAttendanceFactory extends Factory
{
    protected $model = SessionAttendance::class;

    public function definition(): array
    {
        return [
            'campaign_session_id' => CampaignSession::factory(),
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(SessionAttendance::statuses()),
            'note' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
            'responded_at' => now(),
        ];
    }
}
