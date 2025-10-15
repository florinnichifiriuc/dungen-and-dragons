<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Region;
use App\Models\User;

class RegionPolicy
{
    /**
     * Determine whether the user can view a region.
     */
    public function view(User $user, Region $region): bool
    {
        return $this->hasGroupRole($user, $region->group);
    }

    /**
     * Determine whether the user can create regions within the group.
     */
    public function create(User $user, Group $group): bool
    {
        return $this->hasGroupRole($user, $group);
    }

    /**
     * Determine whether the user can update the region.
     */
    public function update(User $user, Region $region): bool
    {
        return $this->hasGroupRole($user, $region->group);
    }

    /**
     * Determine whether the user can delete the region.
     */
    public function delete(User $user, Region $region): bool
    {
        return $this->hasGroupRole($user, $region->group, [GroupMembership::ROLE_OWNER]);
    }

    public function delegateToAi(User $user, Region $region): bool
    {
        return $this->hasGroupRole($user, $region->group);
    }

    /**
     * Check if the user has the necessary group role.
     *
     * @param  array<int, string>|null  $roles
     */
    protected function hasGroupRole(User $user, Group $group, ?array $roles = null): bool
    {
        $query = $group->memberships()
            ->where('user_id', $user->getKey());

        if ($roles !== null) {
            $query->whereIn('role', $roles);
        } else {
            $query->whereIn('role', [
                GroupMembership::ROLE_OWNER,
                GroupMembership::ROLE_DUNGEON_MASTER,
            ]);
        }

        return $query->exists();
    }
}
