<?php

namespace Database\Factories;

use App\Models\ConditionTransparencyWebhook;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConditionTransparencyWebhook>
 */
class ConditionTransparencyWebhookFactory extends Factory
{
    protected $model = ConditionTransparencyWebhook::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'url' => $this->faker->url(),
            'secret' => bin2hex(random_bytes(16)),
            'active' => true,
        ];
    }
}
