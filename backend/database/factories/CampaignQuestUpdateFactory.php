<?php

namespace Database\Factories;

use App\Models\CampaignQuest;
use App\Models\CampaignQuestUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignQuestUpdate>
 */
class CampaignQuestUpdateFactory extends Factory
{
    protected $model = CampaignQuestUpdate::class;

    public function definition(): array
    {
        return [
            'quest_id' => CampaignQuest::factory(),
            'created_by_id' => User::factory(),
            'summary' => fake()->sentence(),
            'details' => fake()->optional()->paragraphs(2, true),
            'recorded_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
