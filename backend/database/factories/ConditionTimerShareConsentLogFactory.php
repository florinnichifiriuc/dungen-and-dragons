<?php

namespace Database\Factories;

use App\Models\ConditionTimerShareConsentLog;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConditionTimerShareConsentLog>
 */
class ConditionTimerShareConsentLogFactory extends Factory
{
    protected $model = ConditionTimerShareConsentLog::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'user_id' => User::factory(),
            'recorded_by' => User::factory(),
            'action' => 'granted',
            'visibility' => 'counts',
            'source' => 'facilitator',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
