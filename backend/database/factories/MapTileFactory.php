<?php

namespace Database\Factories;

use App\Models\Map;
use App\Models\MapTile;
use App\Models\TileTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapTile>
 */
class MapTileFactory extends Factory
{
    protected $model = MapTile::class;

    public function definition(): array
    {
        return [
            'map_id' => Map::factory(),
            'tile_template_id' => TileTemplate::factory(),
            'q' => $this->faker->numberBetween(-5, 5),
            'r' => $this->faker->numberBetween(-5, 5),
            'orientation' => 'pointy',
            'elevation' => $this->faker->numberBetween(-2, 4),
            'variant' => null,
            'locked' => false,
        ];
    }
}
