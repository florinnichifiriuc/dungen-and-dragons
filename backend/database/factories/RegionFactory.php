<?php

namespace Database\Factories;

use App\Models\Region;
use App\Models\World;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    protected $model = Region::class;

    public function definition(): array
    {
        return [
            'world_id' => World::factory(),
            'name' => $this->faker->unique()->words(2, true),
            'summary' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
        ];
    }

    public function configure(): static
    {
        return $this
            ->afterMaking(function (Region $region): void {
                if ($region->group_id === null) {
                    $this->syncGroupFromWorld($region);
                }
            })
            ->afterCreating(function (Region $region): void {
                if ($region->group_id === null) {
                    $this->syncGroupFromWorld($region, persist: true);
                }
            });
    }

    protected function syncGroupFromWorld(Region $region, bool $persist = false): void
    {
        if ($region->relationLoaded('world') && $region->world !== null) {
            $region->group()->associate($region->world->group);
        } elseif ($region->world_id !== null) {
            $world = World::query()->find($region->world_id);
            if ($world !== null) {
                $region->group()->associate($world->group);
            }
        }

        if ($persist && $region->isDirty('group_id')) {
            $region->save();
        }
    }
}
