<?php

namespace App\Policies;

use App\Models\DiceRoll;
use App\Models\CampaignSession;
use App\Models\User;

class DiceRollPolicy
{
    public function create(User $user, CampaignSession $session): bool
    {
        return app(SessionPolicy::class)->view($user, $session);
    }

    public function delete(User $user, DiceRoll $roll): bool
    {
        if ($roll->roller_id === $user->id) {
            return true;
        }

        return app(CampaignPolicy::class)->update($user, $roll->campaign);
    }
}
