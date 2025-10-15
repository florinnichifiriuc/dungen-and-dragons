<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Map;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Map>
 */
class MapFactory extends Factory
{
    protected $model = Map::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'region_id' => Region::factory(),
            'title' => $this->faker->words(3, true),
            'base_layer' => 'hex',
            'orientation' => 'pointy',
            'width' => 20,
            'height' => 20,
            'gm_only' => false,
            'fog_data' => null,
        ];
    }
}
