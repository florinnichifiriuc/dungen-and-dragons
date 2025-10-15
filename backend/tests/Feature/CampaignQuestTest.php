<?php

use App\Models\Campaign;
use App\Models\CampaignQuest;
use App\Models\CampaignQuestUpdate;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setupCampaignWithMembers(): array
{
    $manager = User::factory()->create();
    $group = Group::factory()->for($manager, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $region = Region::factory()->for($group)->create();

    $campaign = Campaign::factory()->for($group)->create([
        'region_id' => $region->id,
        'created_by' => $manager->id,
    ]);

    $player = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    return [$manager, $campaign, $group, $player, $region];
}

it('allows campaign managers to create quests with metadata', function () {
    [$manager, $campaign, $group, , $region] = setupCampaignWithMembers();

    $response = $this->actingAs($manager)->post(route('campaigns.quests.store', $campaign), [
        'title' => 'Shadow over Emberfall',
        'summary' => 'Recover the emberstone before the eclipse ritual.',
        'details' => 'The Moon Court has hidden the emberstone in the catacombs beneath Emberfall Keep.',
        'status' => CampaignQuest::STATUS_PLANNED,
        'priority' => CampaignQuest::PRIORITY_HIGH,
        'region_id' => $region->id,
        'target_turn_number' => 6,
        'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
    ]);

    $response->assertRedirect();

    $quest = CampaignQuest::query()->where('campaign_id', $campaign->id)->first();

    expect($quest)->not->toBeNull();
    expect($quest->title)->toBe('Shadow over Emberfall');
    expect($quest->status)->toBe(CampaignQuest::STATUS_PLANNED);
    expect($quest->priority)->toBe(CampaignQuest::PRIORITY_HIGH);
    expect($quest->region_id)->toBe($region->id);
    expect($quest->target_turn_number)->toBe(6);
    expect($quest->starts_at)->not->toBeNull();
});

it('prevents players from updating quests without management rights', function () {
    [$manager, $campaign, $group, $player] = setupCampaignWithMembers();

    $quest = CampaignQuest::factory()
        ->for($campaign)
        ->for($group->regions()->first(), 'region')
        ->create([
            'created_by_id' => $manager->id,
            'status' => CampaignQuest::STATUS_PLANNED,
        ]);

    $response = $this->actingAs($player)->put(route('campaigns.quests.update', [$campaign, $quest]), [
        'title' => $quest->title,
        'summary' => 'Updated summary',
        'details' => 'Revised details',
        'status' => CampaignQuest::STATUS_ACTIVE,
        'priority' => CampaignQuest::PRIORITY_STANDARD,
        'region_id' => '',
        'target_turn_number' => '',
        'starts_at' => '',
        'completed_at' => '',
        'archived_at' => '',
    ]);

    $response->assertForbidden();

    $quest->refresh();
    expect($quest->status)->toBe(CampaignQuest::STATUS_PLANNED);
});

it('allows campaign members to log and delete their own quest updates', function () {
    [$manager, $campaign, $group, $player] = setupCampaignWithMembers();

    $quest = CampaignQuest::factory()
        ->for($campaign)
        ->create([
            'created_by_id' => $manager->id,
            'status' => CampaignQuest::STATUS_ACTIVE,
            'priority' => CampaignQuest::PRIORITY_STANDARD,
        ]);

    $response = $this->actingAs($player)->post(route('campaigns.quests.updates.store', [$campaign, $quest]), [
        'summary' => 'Tracked the emberstone to the Moon Court archives.',
        'details' => 'Lysandra brokered a meeting with the archivist and uncovered an encrypted ledger.',
        'recorded_at' => now()->format('Y-m-d\TH:i'),
    ]);

    $response->assertRedirect();

    $update = CampaignQuestUpdate::query()->where('quest_id', $quest->id)->first();

    expect($update)->not->toBeNull();
    expect($update->summary)->toContain('Tracked the emberstone');
    expect($update->created_by_id)->toBe($player->id);

    $deleteResponse = $this->actingAs($player)->delete(route('campaigns.quests.updates.destroy', [$campaign, $quest, $update]));
    $deleteResponse->assertRedirect();

    expect(CampaignQuestUpdate::query()->where('quest_id', $quest->id)->exists())->toBeFalse();
});
