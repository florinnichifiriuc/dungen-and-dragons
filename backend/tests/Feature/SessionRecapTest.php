<?php

use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\SessionRecap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createSessionWithMembersForRecap(): array
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

it('allows campaign members to share recaps', function () {
    [$campaign, $session, , $player] = createSessionWithMembersForRecap();

    $response = $this->actingAs($player)->post(route('campaigns.sessions.recaps.store', [$campaign, $session]), [
        'title' => 'Clash at the obsidian gate',
        'body' => 'We parleyed with the lich and barely survived the curse.',
    ]);

    $response->assertRedirect();

    $recap = SessionRecap::query()
        ->where('campaign_session_id', $session->id)
        ->where('author_id', $player->id)
        ->first();

    expect($recap)->not->toBeNull();
    expect($recap->title)->toBe('Clash at the obsidian gate');
    expect($recap->body)->toContain('lich');
});

it('prevents outsiders from sharing recaps', function () {
    [$campaign, $session] = createSessionWithMembersForRecap();
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)->post(route('campaigns.sessions.recaps.store', [$campaign, $session]), [
        'body' => 'I should not be allowed to post this.',
    ]);

    $response->assertForbidden();

    expect(SessionRecap::query()->exists())->toBeFalse();
});

it('allows authors to delete their recaps', function () {
    [$campaign, $session, , $player] = createSessionWithMembersForRecap();

    $recap = SessionRecap::factory()->create([
        'campaign_id' => $campaign->id,
        'campaign_session_id' => $session->id,
        'author_id' => $player->id,
        'title' => 'Final stand',
    ]);

    $response = $this->actingAs($player)->delete(route('campaigns.sessions.recaps.destroy', [
        'campaign' => $campaign,
        'session' => $session,
        'recap' => $recap,
    ]));

    $response->assertRedirect();

    expect(SessionRecap::query()->exists())->toBeFalse();
});

it('allows managers to moderate recaps while blocking other players', function () {
    [$campaign, $session, $manager, $author, $group] = createSessionWithMembersForRecap();
    $otherPlayer = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $otherPlayer->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $recap = SessionRecap::factory()->create([
        'campaign_id' => $campaign->id,
        'campaign_session_id' => $session->id,
        'author_id' => $author->id,
        'body' => 'Author-only lore.',
    ]);

    $this->actingAs($otherPlayer)
        ->delete(route('campaigns.sessions.recaps.destroy', [
            'campaign' => $campaign,
            'session' => $session,
            'recap' => $recap,
        ]))
        ->assertForbidden();

    expect(SessionRecap::query()->whereKey($recap->id)->exists())->toBeTrue();

    $this->actingAs($manager)
        ->delete(route('campaigns.sessions.recaps.destroy', [
            'campaign' => $campaign,
            'session' => $session,
            'recap' => $recap,
        ]))
        ->assertRedirect();

    expect(SessionRecap::query()->whereKey($recap->id)->exists())->toBeFalse();
});
