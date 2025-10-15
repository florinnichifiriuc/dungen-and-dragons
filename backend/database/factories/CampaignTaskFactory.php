<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignTask;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignTask>
 */
class CampaignTaskFactory extends Factory
{
    protected $model = CampaignTask::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'created_by_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'status' => CampaignTask::STATUS_BACKLOG,
            'position' => 0,
            'due_turn_number' => $this->faker->optional()->numberBetween(1, 20),
            'assigned_user_id' => null,
            'assigned_group_id' => $this->faker->boolean(30) ? Group::factory() : null,
        ];
    }
}
