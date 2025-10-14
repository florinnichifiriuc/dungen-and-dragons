<?php

namespace Database\Factories;

use App\Models\CampaignSession;
use App\Models\InitiativeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InitiativeEntry>
 */
class InitiativeEntryFactory extends Factory
{
    protected $model = InitiativeEntry::class;

    public function definition(): array
    {
        return [
            'campaign_session_id' => CampaignSession::factory(),
            'name' => $this->faker->firstName(),
            'entity_type' => null,
            'entity_id' => null,
            'dexterity_mod' => $this->faker->numberBetween(-1, 5),
            'initiative' => $this->faker->numberBetween(1, 20),
            'is_current' => false,
            'order_index' => $this->faker->numberBetween(0, 10),
        ];
    }
}
