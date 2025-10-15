<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\User;

class MapPolicy
{
    public function view(User $user, Map $map): bool
    {
        return $this->hasBuilderRole($user, $map->group);
    }

    public function create(User $user, Group $group): bool
    {
        return $this->hasBuilderRole($user, $group);
    }

    public function update(User $user, Map $map): bool
    {
        return $this->hasBuilderRole($user, $map->group);
    }

    public function delete(User $user, Map $map): bool
    {
        return $this->hasGroupRole($user, $map->group, [GroupMembership::ROLE_OWNER]);
    }

    protected function hasBuilderRole(User $user, Group $group): bool
    {
        return $this->hasGroupRole($user, $group);
    }

    /**
     * @param  array<int, string>|null  $roles
     */
    protected function hasGroupRole(User $user, Group $group, ?array $roles = null): bool
    {
        $query = $group->memberships()->where('user_id', $user->getAuthIdentifier());

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
