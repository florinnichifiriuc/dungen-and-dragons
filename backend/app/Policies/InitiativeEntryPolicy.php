<?php

namespace App\Policies;

use App\Models\InitiativeEntry;
use App\Models\CampaignSession;
use App\Models\User;

class InitiativeEntryPolicy
{
    public function create(User $user, CampaignSession $session): bool
    {
        return app(CampaignPolicy::class)->update($user, $session->campaign);
    }

    public function update(User $user, InitiativeEntry $entry): bool
    {
        return app(CampaignPolicy::class)->update($user, $entry->session->campaign);
    }

    public function delete(User $user, InitiativeEntry $entry): bool
    {
        return app(CampaignPolicy::class)->update($user, $entry->session->campaign);
    }
}
