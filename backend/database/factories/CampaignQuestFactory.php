<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignQuest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignQuest>
 */
class CampaignQuestFactory extends Factory
{
    protected $model = CampaignQuest::class;

    public function definition(): array
    {
        $statuses = CampaignQuest::statuses();
        $priorities = CampaignQuest::priorities();

        return [
            'campaign_id' => Campaign::factory(),
            'created_by_id' => User::factory(),
            'title' => fake()->sentence(4),
            'summary' => fake()->paragraph(),
            'details' => fake()->paragraphs(2, true),
            'status' => $statuses[array_rand($statuses)],
            'priority' => $priorities[array_rand($priorities)],
            'target_turn_number' => fake()->numberBetween(1, 12),
            'starts_at' => fake()->optional()->dateTimeBetween('-2 weeks', '+1 week'),
            'completed_at' => null,
            'archived_at' => null,
        ];
    }

    public function planned(): self
    {
        return $this->state(fn () => ['status' => CampaignQuest::STATUS_PLANNED]);
    }

    public function active(): self
    {
        return $this->state(fn () => ['status' => CampaignQuest::STATUS_ACTIVE]);
    }

    public function completed(): self
    {
        return $this->state(fn () => ['status' => CampaignQuest::STATUS_COMPLETED, 'completed_at' => now()]);
    }

    public function failed(): self
    {
        return $this->state(fn () => ['status' => CampaignQuest::STATUS_FAILED]);
    }
}
