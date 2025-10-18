<?php

namespace App\Policies;

use App\Models\BugReport;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BugReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return (bool) $user->is_support_admin;
    }

    public function view(User $user, BugReport $report): bool
    {
        if ($user->is_support_admin) {
            return true;
        }

        if ($report->submitted_by === $user->getKey()) {
            return true;
        }

        if ($report->assigned_to === $user->getKey()) {
            return true;
        }

        if ($report->group_id) {
            return $user->groupMemberships()
                ->where('group_id', $report->group_id)
                ->whereIn('role', [
                    GroupMembership::ROLE_OWNER,
                    GroupMembership::ROLE_DUNGEON_MASTER,
                ])
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, BugReport $report): bool
    {
        return $user->is_support_admin || $report->assigned_to === $user->getKey();
    }

    public function assign(User $user, BugReport $report): bool
    {
        return $user->is_support_admin;
    }

    public function addComment(User $user, BugReport $report): bool
    {
        return $this->view($user, $report);
    }
}
