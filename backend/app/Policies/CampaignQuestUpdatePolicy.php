<?php

namespace App\Policies;

use App\Models\CampaignQuest;
use App\Models\CampaignQuestUpdate;
use App\Models\User;

class CampaignQuestUpdatePolicy
{
    public function create(User $user, CampaignQuest $quest): bool
    {
        return app(CampaignPolicy::class)->view($user, $quest->campaign);
    }

    public function delete(User $user, CampaignQuestUpdate $update): bool
    {
        if ($update->created_by_id === $user->id) {
            return true;
        }

        return app(CampaignPolicy::class)->update($user, $update->quest->campaign);
    }
}
