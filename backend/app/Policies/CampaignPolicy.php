<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\CampaignRoleAssignment;
use App\Models\GroupMembership;
use App\Models\Group;
use App\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->groups()->exists();
    }

    public function view(User $user, Campaign $campaign): bool
    {
        if ($campaign->created_by === $user->id) {
            return true;
        }

        $groupMembership = $campaign->group->memberships()
            ->where('user_id', $user->id)
            ->exists();

        if ($groupMembership) {
            return true;
        }

        return $campaign->roleAssignments()
            ->where(function ($query) use ($user): void {
                $query->where(function ($subQuery) use ($user): void {
                    $subQuery->where('assignee_type', User::class)
                        ->where('assignee_id', $user->id);
                })->orWhere(function ($subQuery) use ($user): void {
                    $subQuery->where('assignee_type', Group::class)
                        ->whereIn('assignee_id', $user->groups()->pluck('groups.id'));
                });
            })
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->groupMemberships()->exists();
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $this->canManage($user, $campaign);
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $this->canManage($user, $campaign);
    }

    public function previewDigest(User $user, Campaign $campaign): bool
    {
        return $this->canManage($user, $campaign);
    }

    public function viewInsights(User $user, Campaign $campaign): bool
    {
        return $this->canManage($user, $campaign);
    }

    protected function canManage(User $user, Campaign $campaign): bool
    {
        if ($campaign->created_by === $user->id) {
            return true;
        }

        $manager = $campaign->group->memberships()
            ->where('user_id', $user->id)
            ->whereIn('role', [
                GroupMembership::ROLE_OWNER,
                GroupMembership::ROLE_DUNGEON_MASTER,
            ])
            ->exists();

        if ($manager) {
            return true;
        }

        return $campaign->roleAssignments()
            ->where('assignee_type', User::class)
            ->where('assignee_id', $user->id)
            ->where('role', CampaignRoleAssignment::ROLE_GM)
            ->exists();
    }
}
