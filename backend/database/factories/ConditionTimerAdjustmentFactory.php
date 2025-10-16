<?php

namespace Database\Factories;

use App\Models\ConditionTimerAdjustment;
use App\Models\Group;
use App\Models\MapToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ConditionTimerAdjustment>
 */
class ConditionTimerAdjustmentFactory extends Factory
{
    protected $model = ConditionTimerAdjustment::class;

    public function definition(): array
    {
        $token = MapToken::factory()->create();
        $token->load('map');

        /** @var Group $group */
        $group = $token->map->group ?? Group::factory()->create();
        $actor = User::factory()->create();

        return [
            'group_id' => $group->id,
            'map_token_id' => $token->id,
            'condition_key' => $this->faker->randomElement(MapToken::CONDITIONS),
            'previous_rounds' => $this->faker->numberBetween(1, 5),
            'new_rounds' => $this->faker->numberBetween(1, 5),
            'delta' => $this->faker->numberBetween(-3, 3),
            'reason' => 'manual_adjustment',
            'context' => ['source' => 'factory'],
            'actor_id' => $actor->id,
            'actor_role' => 'dungeon-master',
            'recorded_at' => Carbon::now('UTC'),
        ];
    }
}
