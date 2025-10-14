<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\DiceRoll;
use App\Models\CampaignSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiceRoll>
 */
class DiceRollFactory extends Factory
{
    protected $model = DiceRoll::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'campaign_session_id' => CampaignSession::factory(),
            'roller_id' => User::factory(),
            'expression' => '2d6+3',
            'result_breakdown' => [
                'dice' => [4, 6],
                'modifier' => 3,
            ],
            'result_total' => 13,
        ];
    }
}
