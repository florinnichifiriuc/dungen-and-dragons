<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use App\Models\World;

class WorldPolicy
{
    public function view(User $user, World $world): bool
    {
        return $this->hasGroupRole($user, $world->group);
    }

    public function create(User $user, Group $group): bool
    {
        return $this->hasGroupRole($user, $group);
    }

    public function update(User $user, World $world): bool
    {
        return $this->hasGroupRole($user, $world->group);
    }

    public function delete(User $user, World $world): bool
    {
        return $this->hasGroupRole($user, $world->group, [GroupMembership::ROLE_OWNER]);
    }

    /**
     * Determine whether the user has the required group role.
     *
     * @param  array<int, string>|null  $roles
     */
    protected function hasGroupRole(User $user, Group $group, ?array $roles = null): bool
    {
        $query = $group->memberships()->where('user_id', $user->getKey());

        if ($roles === null) {
            $query->whereIn('role', [
                GroupMembership::ROLE_OWNER,
                GroupMembership::ROLE_DUNGEON_MASTER,
            ]);
        } else {
            $query->whereIn('role', $roles);
        }

        return $query->exists();
    }
}
