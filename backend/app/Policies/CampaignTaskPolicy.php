<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\CampaignRoleAssignment;
use App\Models\CampaignTask;
use App\Models\User;

class CampaignTaskPolicy
{
    public function viewAny(User $user, Campaign $campaign): bool
    {
        return app(CampaignPolicy::class)->view($user, $campaign);
    }

    public function view(User $user, CampaignTask $task): bool
    {
        return app(CampaignPolicy::class)->view($user, $task->campaign);
    }

    public function create(User $user, Campaign $campaign): bool
    {
        return app(CampaignPolicy::class)->update($user, $campaign);
    }

    public function update(User $user, CampaignTask $task): bool
    {
        $campaign = $task->campaign;

        if (app(CampaignPolicy::class)->update($user, $campaign)) {
            return true;
        }

        if ($task->assigned_user_id === $user->id) {
            return true;
        }

        if ($task->assigned_group_id !== null) {
            $hasGroupAccess = $user->groupMemberships()
                ->where('group_id', $task->assigned_group_id)
                ->exists();

            if ($hasGroupAccess) {
                return true;
            }
        }

        $isGmAssignee = $campaign->roleAssignments()
            ->where('assignee_type', User::class)
            ->where('assignee_id', $user->id)
            ->where('role', CampaignRoleAssignment::ROLE_GM)
            ->where('status', CampaignRoleAssignment::STATUS_ACTIVE)
            ->exists();

        if ($isGmAssignee) {
            return true;
        }

        return false;
    }

    public function delete(User $user, CampaignTask $task): bool
    {
        return app(CampaignPolicy::class)->update($user, $task->campaign);
    }

    public function reorder(User $user, Campaign $campaign): bool
    {
        return app(CampaignPolicy::class)->update($user, $campaign);
    }
}
