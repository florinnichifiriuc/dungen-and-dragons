<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupMembership>
 */
class GroupMembershipFactory extends Factory
{
    protected $model = GroupMembership::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'user_id' => User::factory(),
            'role' => $this->faker->randomElement([
                GroupMembership::ROLE_OWNER,
                GroupMembership::ROLE_DUNGEON_MASTER,
                GroupMembership::ROLE_PLAYER,
            ]),
        ];
    }

    public function owner(): self
    {
        return $this->state(fn () => ['role' => GroupMembership::ROLE_OWNER]);
    }

    public function dungeonMaster(): self
    {
        return $this->state(fn () => ['role' => GroupMembership::ROLE_DUNGEON_MASTER]);
    }

    public function player(): self
    {
        return $this->state(fn () => ['role' => GroupMembership::ROLE_PLAYER]);
    }
}
