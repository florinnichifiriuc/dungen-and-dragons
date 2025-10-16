<?php

namespace Database\Factories;

use App\Models\ConditionTimerSummaryShare;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConditionTimerSummaryShare>
 */
class ConditionTimerSummaryShareFactory extends Factory
{
    protected $model = ConditionTimerSummaryShare::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'created_by' => User::factory(),
            'token' => Str::random(48),
            'expires_at' => now('UTC')->addDays(7),
        ];
    }
}
