<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\CampaignEntity;
use App\Models\User;

class CampaignEntityPolicy
{
    public function viewAny(User $user, Campaign $campaign): bool
    {
        return app(CampaignPolicy::class)->view($user, $campaign);
    }

    public function view(User $user, CampaignEntity $entity): bool
    {
        return app(CampaignPolicy::class)->view($user, $entity->campaign);
    }

    public function create(User $user, Campaign $campaign): bool
    {
        return app(CampaignPolicy::class)->update($user, $campaign);
    }

    public function update(User $user, CampaignEntity $entity): bool
    {
        return app(CampaignPolicy::class)->update($user, $entity->campaign);
    }

    public function delete(User $user, CampaignEntity $entity): bool
    {
        return app(CampaignPolicy::class)->update($user, $entity->campaign);
    }
}
