<?php

use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\SessionReward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createSessionWithMembersForRewards(): array
{
    $manager = User::factory()->create();
    $group = Group::factory()->for($manager, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $campaign = Campaign::factory()->for($group)->create([
        'created_by' => $manager->id,
    ]);

    $session = CampaignSession::factory()->for($campaign)->create([
        'created_by' => $manager->id,
    ]);

    $player = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    return [$campaign, $session, $manager, $player, $group];
}

it('allows campaign members to log rewards for a session', function () {
    [$campaign, $session, , $player] = createSessionWithMembersForRewards();

    $response = $this->actingAs($player)->post(route('campaigns.sessions.rewards.store', [$campaign, $session]), [
        'reward_type' => SessionReward::TYPE_LOOT,
        'title' => 'Ruby diadem',
        'quantity' => 1,
        'notes' => 'Recovered from the wyrmling hoard.',
    ]);

    $response->assertRedirect();

    $reward = SessionReward::query()
        ->where('campaign_session_id', $session->id)
        ->where('recorded_by', $player->id)
        ->first();

    expect($reward)->not->toBeNull();
    expect($reward->title)->toBe('Ruby diadem');
    expect($reward->reward_type)->toBe(SessionReward::TYPE_LOOT);
});

it('prevents outsiders from logging rewards', function () {
    [$campaign, $session] = createSessionWithMembersForRewards();
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)->post(route('campaigns.sessions.rewards.store', [$campaign, $session]), [
        'reward_type' => SessionReward::TYPE_LOOT,
        'title' => 'Should not work',
    ]);

    $response->assertForbidden();

    expect(SessionReward::query()->exists())->toBeFalse();
});

it('allows recorders or managers to remove rewards while blocking other members', function () {
    [$campaign, $session, $manager, $player, $group] = createSessionWithMembersForRewards();
    $otherPlayer = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $otherPlayer->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $reward = SessionReward::factory()->create([
        'campaign_id' => $campaign->id,
        'campaign_session_id' => $session->id,
        'recorded_by' => $player->id,
        'reward_type' => SessionReward::TYPE_BOON,
        'title' => 'Blessing of the Grove',
    ]);

    $this->actingAs($otherPlayer)
        ->delete(route('campaigns.sessions.rewards.destroy', [
            'campaign' => $campaign,
            'session' => $session,
            'reward' => $reward,
        ]))
        ->assertForbidden();

    expect(SessionReward::query()->whereKey($reward->id)->exists())->toBeTrue();

    $this->actingAs($manager)
        ->delete(route('campaigns.sessions.rewards.destroy', [
            'campaign' => $campaign,
            'session' => $session,
            'reward' => $reward,
        ]))
        ->assertRedirect();

    expect(SessionReward::query()->whereKey($reward->id)->exists())->toBeFalse();
});
