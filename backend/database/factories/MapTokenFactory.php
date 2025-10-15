<?php

namespace Database\Factories;

use App\Models\Map;
use App\Models\MapToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapToken>
 */
class MapTokenFactory extends Factory
{
    protected $model = MapToken::class;

    public function definition(): array
    {
        $sizes = ['tiny', 'small', 'medium', 'large', 'huge'];
        $factions = [
            \App\Models\MapToken::FACTION_ALLIED,
            \App\Models\MapToken::FACTION_HOSTILE,
            \App\Models\MapToken::FACTION_NEUTRAL,
            \App\Models\MapToken::FACTION_HAZARD,
        ];

        $maxHitPoints = $this->faker->optional()->numberBetween(6, 180);
        $hitPoints = $this->faker->optional()->numberBetween(-10, 180);

        if ($maxHitPoints !== null && $hitPoints !== null) {
            $hitPoints = min($hitPoints, $maxHitPoints);
        }

        return [
            'map_id' => Map::factory(),
            'name' => $this->faker->words(2, true),
            'x' => $this->faker->numberBetween(-50, 50),
            'y' => $this->faker->numberBetween(-50, 50),
            'color' => $this->faker->safeHexColor(),
            'size' => $this->faker->randomElement($sizes),
            'faction' => $this->faker->randomElement($factions),
            'initiative' => $this->faker->optional(0.6)->numberBetween(-5, 30),
            'status_effects' => $this->faker->optional()->words(3, true),
            'hit_points' => $hitPoints,
            'temporary_hit_points' => $this->faker->optional()->numberBetween(0, 25),
            'max_hit_points' => $maxHitPoints,
            'z_index' => $this->faker->numberBetween(-20, 20),
            'hidden' => $this->faker->boolean(10),
            'gm_note' => $this->faker->optional()->sentence(),
        ];
    }
}
