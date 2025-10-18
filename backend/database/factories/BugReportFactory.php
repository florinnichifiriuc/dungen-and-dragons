<?php

namespace Database\Factories;

use App\Models\BugReport;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BugReport>
 */
class BugReportFactory extends Factory
{
    protected $model = BugReport::class;

    public function definition(): array
    {
        return [
            'reference' => 'BR-'.strtoupper($this->faker->lexify('??????')),
            'submitted_by' => User::factory(),
            'group_id' => Group::factory(),
            'context_type' => 'facilitator',
            'context_identifier' => null,
            'status' => BugReport::STATUS_OPEN,
            'priority' => BugReport::PRIORITY_NORMAL,
            'summary' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'environment' => [
                'user_agent' => $this->faker->userAgent(),
            ],
            'ai_context' => [],
            'tags' => [],
        ];
    }
}
