<?php

namespace Database\Factories;

use App\Models\ConditionTransparencyExport;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConditionTransparencyExport>
 */
class ConditionTransparencyExportFactory extends Factory
{
    protected $model = ConditionTransparencyExport::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'requested_by' => User::factory(),
            'format' => 'csv',
            'visibility_mode' => 'counts',
            'filters' => [],
            'status' => ConditionTransparencyExport::STATUS_PENDING,
        ];
    }
}
