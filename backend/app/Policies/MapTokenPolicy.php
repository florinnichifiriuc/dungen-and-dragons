<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\User;

class MapTokenPolicy
{
    public function create(User $user, Map $map): bool
    {
        return $this->hasBuilderRole($user, $map->group);
    }

    public function update(User $user, MapToken $token): bool
    {
        return $this->hasBuilderRole($user, $token->map->group);
    }

    public function delete(User $user, MapToken $token): bool
    {
        return $this->hasBuilderRole($user, $token->map->group);
    }

    protected function hasBuilderRole(User $user, Group $group): bool
    {
        return $group
            ->memberships()
            ->where('user_id', $user->getAuthIdentifier())
            ->whereIn('role', [
                GroupMembership::ROLE_OWNER,
                GroupMembership::ROLE_DUNGEON_MASTER,
            ])
            ->exists();
    }
}
