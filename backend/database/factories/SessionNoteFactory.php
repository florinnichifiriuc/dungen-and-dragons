<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\SessionNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionNote>
 */
class SessionNoteFactory extends Factory
{
    protected $model = SessionNote::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'campaign_session_id' => CampaignSession::factory(),
            'author_id' => User::factory(),
            'visibility' => SessionNote::VISIBILITY_PLAYERS,
            'is_pinned' => false,
            'content' => $this->faker->paragraph(),
        ];
    }
}
