<?php

use App\Models\Campaign;
use App\Models\CampaignTask;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createCampaignWithManager(): array
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

    return [$manager, $campaign, $group];
}

it('allows campaign managers to create turn-bound tasks', function () {
    [$manager, $campaign, $group] = createCampaignWithManager();

    $adventurer = User::factory()->create();
    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $adventurer->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $response = $this->actingAs($manager)->post(route('campaigns.tasks.store', $campaign), [
        'title' => 'Secure the river ford',
        'description' => 'Build defenses before the rival guild arrives.',
        'status' => CampaignTask::STATUS_READY,
        'due_turn_number' => 3,
        'assigned_user_id' => $adventurer->id,
    ]);

    $response->assertRedirect(route('campaigns.tasks.index', $campaign));

    $this->assertDatabaseHas('campaign_tasks', [
        'campaign_id' => $campaign->id,
        'title' => 'Secure the river ford',
        'status' => CampaignTask::STATUS_READY,
        'due_turn_number' => 3,
        'assigned_user_id' => $adventurer->id,
    ]);
});

it('allows assigned adventurers to progress their own tasks', function () {
    [$manager, $campaign, $group] = createCampaignWithManager();

    $player = User::factory()->create();
    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $task = CampaignTask::factory()
        ->for($campaign)
        ->create([
            'created_by_id' => $manager->id,
            'assigned_user_id' => $player->id,
            'assigned_group_id' => null,
            'status' => CampaignTask::STATUS_ACTIVE,
            'position' => 0,
        ]);

    $this->actingAs($player)
        ->patch(route('campaigns.tasks.update', [$campaign, $task]), [
            'status' => CampaignTask::STATUS_COMPLETED,
        ])
        ->assertRedirect(route('campaigns.tasks.index', $campaign));

    $this->assertDatabaseHas('campaign_tasks', [
        'id' => $task->id,
        'status' => CampaignTask::STATUS_COMPLETED,
    ]);

    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->patch(route('campaigns.tasks.update', [$campaign, $task]), [
            'status' => CampaignTask::STATUS_READY,
        ])
        ->assertForbidden();
});

it('reorders tasks within a column when managers adjust priority', function () {
    [$manager, $campaign] = createCampaignWithManager();

    $first = CampaignTask::factory()->for($campaign)->create([
        'created_by_id' => $manager->id,
        'assigned_group_id' => null,
        'assigned_user_id' => null,
        'status' => CampaignTask::STATUS_BACKLOG,
        'position' => 0,
        'title' => 'Scout the ruins',
    ]);

    $second = CampaignTask::factory()->for($campaign)->create([
        'created_by_id' => $manager->id,
        'assigned_group_id' => null,
        'assigned_user_id' => null,
        'status' => CampaignTask::STATUS_BACKLOG,
        'position' => 1,
        'title' => 'Recruit allies',
    ]);

    $this->actingAs($manager)
        ->post(route('campaigns.tasks.reorder', $campaign), [
            'status' => CampaignTask::STATUS_BACKLOG,
            'order' => [$second->id, $first->id],
        ])
        ->assertRedirect(route('campaigns.tasks.index', $campaign));

    expect($first->fresh()->position)->toBe(1);
    expect($second->fresh()->position)->toBe(0);
});
