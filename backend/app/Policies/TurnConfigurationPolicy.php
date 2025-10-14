<?php

namespace App\Policies;

use App\Models\GroupMembership;
use App\Models\TurnConfiguration;
use App\Models\User;

class TurnConfigurationPolicy
{
    /**
     * Determine whether the user can update the turn configuration.
     */
    public function update(User $user, TurnConfiguration $turnConfiguration): bool
    {
        return $turnConfiguration->region
            ->group
            ->memberships()
            ->where('user_id', $user->getKey())
            ->whereIn('role', [
                GroupMembership::ROLE_OWNER,
                GroupMembership::ROLE_DUNGEON_MASTER,
            ])
            ->exists();
    }
}
