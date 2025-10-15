<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\CampaignQuest;
use App\Models\User;

class CampaignQuestPolicy
{
    public function viewAny(User $user, Campaign $campaign): bool
    {
        return app(CampaignPolicy::class)->view($user, $campaign);
    }

    public function view(User $user, CampaignQuest $quest): bool
    {
        return app(CampaignPolicy::class)->view($user, $quest->campaign);
    }

    public function create(User $user, Campaign $campaign): bool
    {
        return app(CampaignPolicy::class)->update($user, $campaign);
    }

    public function update(User $user, CampaignQuest $quest): bool
    {
        return app(CampaignPolicy::class)->update($user, $quest->campaign);
    }

    public function delete(User $user, CampaignQuest $quest): bool
    {
        return app(CampaignPolicy::class)->update($user, $quest->campaign);
    }
}
