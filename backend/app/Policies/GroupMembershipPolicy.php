<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;

class GroupMembershipPolicy
{
    public function create(User $user, Group $group): bool
    {
        $membership = $this->membershipFor($user, $group);

        if ($membership === null) {
            return false;
        }

        return in_array($membership->role, [
            GroupMembership::ROLE_OWNER,
            GroupMembership::ROLE_DUNGEON_MASTER,
        ], true);
    }

    public function update(User $user, GroupMembership $membership): bool
    {
        $actorMembership = $this->membershipFor($user, $membership->group);

        if ($actorMembership === null) {
            return false;
        }

        return in_array($actorMembership->role, [
            GroupMembership::ROLE_OWNER,
            GroupMembership::ROLE_DUNGEON_MASTER,
        ], true);
    }

    public function delete(User $user, GroupMembership $membership): bool
    {
        if ($membership->user_id === $user->getAuthIdentifier()) {
            return true;
        }

        $actorMembership = $this->membershipFor($user, $membership->group);

        if ($actorMembership === null) {
            return false;
        }

        return in_array($actorMembership->role, [
            GroupMembership::ROLE_OWNER,
            GroupMembership::ROLE_DUNGEON_MASTER,
        ], true);
    }

    protected function membershipFor(User $user, Group $group): ?GroupMembership
    {
        return $group->memberships()
            ->where('user_id', $user->getKey())
            ->first();
    }
}
