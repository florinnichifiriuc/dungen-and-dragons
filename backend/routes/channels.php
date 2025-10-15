<?php

use App\Models\CampaignSession;
use App\Models\Map;
use App\Policies\CampaignPolicy;
use App\Policies\SessionPolicy;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('campaigns.{campaignId}.sessions.{sessionId}.workspace', function ($user, int $campaignId, int $sessionId) {
    $session = CampaignSession::query()
        ->with('campaign')
        ->whereKey($sessionId)
        ->where('campaign_id', $campaignId)
        ->first();

    if ($session === null) {
        return false;
    }

    /** @var SessionPolicy $policy */
    $policy = app(SessionPolicy::class);

    return $policy->view($user, $session);
});

Broadcast::channel('campaigns.{campaignId}.sessions.{sessionId}.workspace.gms', function ($user, int $campaignId, int $sessionId) {
    $session = CampaignSession::query()
        ->with('campaign')
        ->whereKey($sessionId)
        ->where('campaign_id', $campaignId)
        ->first();

    if ($session === null) {
        return false;
    }

    /** @var CampaignPolicy $policy */
    $policy = app(CampaignPolicy::class);

    return $policy->update($user, $session->campaign);
});

Broadcast::channel('groups.{groupId}.maps.{mapId}', function ($user, int $groupId, int $mapId) {
    $map = Map::query()
        ->whereKey($mapId)
        ->where('group_id', $groupId)
        ->first();

    if ($map === null) {
        return false;
    }

    return $map->group
        ->memberships()
        ->where('user_id', $user->id)
        ->exists();
});
