<?php

namespace Database\Factories;

use App\Models\BugReport;
use App\Models\BugReportUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BugReportUpdate>
 */
class BugReportUpdateFactory extends Factory
{
    protected $model = BugReportUpdate::class;

    public function definition(): array
    {
        return [
            'bug_report_id' => BugReport::factory(),
            'user_id' => User::factory(),
            'type' => 'comment',
            'payload' => [
                'body' => $this->faker->sentence(),
            ],
        ];
    }
}
