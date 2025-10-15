<?php

use App\Models\Campaign;
use App\Models\CampaignRoleAssignment;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('allows invited players to accept email invitations and gain access', function () {
    $manager = User::factory()->create();
    $player = User::factory()->create(['email' => 'player@example.com']);

    $group = Group::factory()->for($manager, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $campaign = Campaign::factory()->for($group)->create([
        'created_by' => $manager->id,
    ]);

    $invitation = $campaign->invitations()->create([
        'email' => $player->email,
        'role' => CampaignRoleAssignment::ROLE_PLAYER,
        'token' => Str::random(40),
        'expires_at' => now()->addDay(),
        'invited_by' => $manager->id,
    ]);

    $response = $this->actingAs($player)->post(route('campaigns.invitations.accept.store', [
        'invitation' => $invitation->token,
    ]));

    $response->assertRedirect(route('campaigns.show', $campaign));

    $invitation->refresh();

    expect($invitation->accepted_at)->not->toBeNull();

    $assignment = $campaign->roleAssignments()
        ->where('assignee_type', User::class)
        ->where('assignee_id', $player->id)
        ->where('role', CampaignRoleAssignment::ROLE_PLAYER)
        ->first();

    expect($assignment)->not->toBeNull();
    expect($assignment->status)->toBe(CampaignRoleAssignment::STATUS_ACTIVE);
    expect($assignment->accepted_at)->not->toBeNull();

    expect(GroupMembership::query()->where('group_id', $group->id)->where('user_id', $player->id)->exists())->toBeTrue();
});

it('allows group managers to accept invitations on behalf of their group', function () {
    $campaignManager = User::factory()->create();
    $campaignGroup = Group::factory()->for($campaignManager, 'creator')->create();

    GroupMembership::create([
        'group_id' => $campaignGroup->id,
        'user_id' => $campaignManager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $campaign = Campaign::factory()->for($campaignGroup)->create([
        'created_by' => $campaignManager->id,
    ]);

    $allyManager = User::factory()->create();
    $allyGroup = Group::factory()->for($allyManager, 'creator')->create();

    GroupMembership::create([
        'group_id' => $allyGroup->id,
        'user_id' => $allyManager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $invitation = $campaign->invitations()->create([
        'group_id' => $allyGroup->id,
        'role' => CampaignRoleAssignment::ROLE_PLAYER,
        'token' => Str::random(40),
        'expires_at' => now()->addDay(),
        'invited_by' => $campaignManager->id,
    ]);

    $response = $this->actingAs($allyManager)->post(route('campaigns.invitations.accept.store', [
        'invitation' => $invitation->token,
    ]));

    $response->assertRedirect(route('campaigns.show', $campaign));

    $invitation->refresh();

    expect($invitation->accepted_at)->not->toBeNull();

    $assignment = $campaign->roleAssignments()
        ->where('assignee_type', Group::class)
        ->where('assignee_id', $allyGroup->id)
        ->where('role', CampaignRoleAssignment::ROLE_PLAYER)
        ->first();

    expect($assignment)->not->toBeNull();
    expect($assignment->status)->toBe(CampaignRoleAssignment::STATUS_ACTIVE);

    expect(GroupMembership::query()->where('group_id', $campaignGroup->id)->where('user_id', $allyManager->id)->exists())->toBeFalse();
});

it('prevents unrelated users from accepting invitations', function () {
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

    $player = User::factory()->create(['email' => 'player@example.com']);
    $other = User::factory()->create(['email' => 'intruder@example.com']);

    $invitation = $campaign->invitations()->create([
        'email' => $player->email,
        'role' => CampaignRoleAssignment::ROLE_PLAYER,
        'token' => Str::random(40),
        'expires_at' => now()->addDay(),
        'invited_by' => $manager->id,
    ]);

    $this->actingAs($other)
        ->post(route('campaigns.invitations.accept.store', [
            'invitation' => $invitation->token,
        ]))
        ->assertForbidden();

    $invitation->refresh();

    expect($invitation->accepted_at)->toBeNull();
    expect($campaign->roleAssignments()->where('assignee_type', User::class)->where('assignee_id', $other->id)->exists())->toBeFalse();
});
