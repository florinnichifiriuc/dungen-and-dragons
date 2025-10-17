<?php

namespace Database\Factories;

use App\Models\ConditionTimerAcknowledgement;
use App\Models\Group;
use App\Models\MapToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConditionTimerAcknowledgement>
 */
class ConditionTimerAcknowledgementFactory extends Factory
{
    protected $model = ConditionTimerAcknowledgement::class;

    public function definition(): array
    {
        $timestamp = $this->faker->dateTimeBetween('-1 hour', 'now', 'UTC');

        return [
            'group_id' => Group::factory(),
            'map_token_id' => MapToken::factory(),
            'user_id' => User::factory(),
            'condition_key' => $this->faker->randomElement(MapToken::CONDITIONS),
            'summary_generated_at' => $timestamp,
            'acknowledged_at' => $timestamp,
            'queued_at' => $timestamp,
            'source' => 'online',
        ];
    }
}
