<?php

namespace App\Policies;

use App\Models\CampaignInvitation;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CampaignInvitationPolicy
{
    use HandlesAuthorization;

    public function respond(User $user, CampaignInvitation $invitation): bool
    {
        if ($invitation->accepted_at !== null) {
            return false;
        }

        if ($invitation->expires_at !== null && $invitation->expires_at->isPast()) {
            return false;
        }

        if ($invitation->group !== null) {
            return $invitation->group
                ->memberships()
                ->where('user_id', $user->id)
                ->whereIn('role', [
                    GroupMembership::ROLE_OWNER,
                    GroupMembership::ROLE_DUNGEON_MASTER,
                ])
                ->exists();
        }

        if ($invitation->email !== null) {
            return strtolower($invitation->email) === strtolower($user->email);
        }

        return false;
    }
}
