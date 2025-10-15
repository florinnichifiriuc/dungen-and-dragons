<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapTile;
use App\Models\User;

class MapTilePolicy
{
    public function create(User $user, Map $map): bool
    {
        return $this->hasBuilderRole($user, $map->group);
    }

    public function update(User $user, MapTile $tile): bool
    {
        if ($tile->locked) {
            return $this->hasGroupRole($user, $tile->map->group, [GroupMembership::ROLE_OWNER]);
        }

        return $this->hasBuilderRole($user, $tile->map->group);
    }

    public function delete(User $user, MapTile $tile): bool
    {
        if ($tile->locked) {
            return $this->hasGroupRole($user, $tile->map->group, [GroupMembership::ROLE_OWNER]);
        }

        return $this->hasBuilderRole($user, $tile->map->group);
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
