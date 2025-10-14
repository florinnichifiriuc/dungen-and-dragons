<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\User;

class SessionPolicy
{
    public function view(User $user, CampaignSession $session): bool
    {
        return app(CampaignPolicy::class)->view($user, $session->campaign);
    }

    public function create(User $user, Campaign $campaign): bool
    {
        return app(CampaignPolicy::class)->update($user, $campaign);
    }

    public function update(User $user, CampaignSession $session): bool
    {
        return app(CampaignPolicy::class)->update($user, $session->campaign);
    }

    public function delete(User $user, CampaignSession $session): bool
    {
        return app(CampaignPolicy::class)->update($user, $session->campaign);
    }
}
