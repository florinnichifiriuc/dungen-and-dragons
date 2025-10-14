<?php

use App\Models\Campaign;
use App\Models\CampaignRoleAssignment;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows group managers to create campaigns', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user, 'creator')->create();
    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $region = Region::factory()->for($group)->create();

    $response = $this->actingAs($user)->post(route('campaigns.store'), [
        'group_id' => $group->id,
        'region_id' => $region->id,
        'title' => 'Shadowfall Saga',
        'synopsis' => 'An epic across fractured realms.',
        'default_timezone' => 'UTC',
        'start_date' => '2025-10-15',
        'turn_hours' => 24,
    ]);

    $campaign = Campaign::where('title', 'Shadowfall Saga')->first();

    expect($campaign)->not->toBeNull();
    expect($campaign->group_id)->toBe($group->id);

    $response->assertRedirect(route('campaigns.show', $campaign));

    $this->assertDatabaseHas('campaign_role_assignments', [
        'campaign_id' => $campaign->id,
        'assignee_type' => User::class,
        'assignee_id' => $user->id,
        'role' => CampaignRoleAssignment::ROLE_GM,
    ]);
});

it('rejects regions that are not part of the group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user, 'creator')->create();
    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $foreignRegion = Region::factory()->create();

    $response = $this->from(route('campaigns.create'))
        ->actingAs($user)
        ->post(route('campaigns.store'), [
            'group_id' => $group->id,
            'region_id' => $foreignRegion->id,
            'title' => 'Shattered Coast',
            'default_timezone' => 'UTC',
            'turn_hours' => 12,
        ]);

    $response->assertSessionHasErrors('region_id');
    $this->assertDatabaseMissing('campaigns', ['title' => 'Shattered Coast']);
});

it('prevents non managers from creating campaigns', function () {
    $user = User::factory()->create();
    $manager = User::factory()->create();
    $group = Group::factory()->for($manager, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $response = $this->actingAs($user)->post(route('campaigns.store'), [
        'group_id' => $group->id,
        'title' => 'Forbidden Depths',
        'default_timezone' => 'UTC',
        'turn_hours' => 18,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('campaigns', ['title' => 'Forbidden Depths']);
});
