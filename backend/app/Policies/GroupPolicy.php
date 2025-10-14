<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;

class GroupPolicy
{
    /**
     * Determine whether the user can view any groups.
     */
    public function viewAny(User $user): bool
    {
        return $user->exists;
    }

    /**
     * Determine whether the user can view the group.
     */
    public function view(User $user, Group $group): bool
    {
        return $this->hasMembership($user, $group);
    }

    /**
     * Determine whether the user can create groups.
     */
    public function create(User $user): bool
    {
        return $user->exists;
    }

    /**
     * Determine whether the user can update the group.
     */
    public function update(User $user, Group $group): bool
    {
        return $this->hasMembership($user, $group, [
            GroupMembership::ROLE_OWNER,
            GroupMembership::ROLE_DUNGEON_MASTER,
        ]);
    }

    /**
     * Determine whether the user can delete the group.
     */
    public function delete(User $user, Group $group): bool
    {
        return $this->hasMembership($user, $group, [GroupMembership::ROLE_OWNER]);
    }

    /**
     * Determine if a user belongs to the group (optionally checking roles).
     *
     * @param  array<int, string>|null  $roles
     */
    protected function hasMembership(User $user, Group $group, ?array $roles = null): bool
    {
        $membership = $group->memberships()
            ->where('user_id', $user->getKey())
            ->first();

        if (! $membership) {
            return false;
        }

        if ($roles === null) {
            return true;
        }

        return in_array($membership->role, $roles, true);
    }
}
