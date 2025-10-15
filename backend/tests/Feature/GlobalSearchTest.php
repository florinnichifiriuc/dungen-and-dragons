<?php

use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\CampaignTask;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\SessionNote;
use App\Models\User;
use App\Services\GlobalSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns accessible records that match the query', function () {
    /** @var GlobalSearchService $service */
    $service = app(GlobalSearchService::class);

    $user = User::factory()->create();
    $group = Group::factory()->for($user, 'creator')->create(['name' => 'Moonlarks']);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $campaign = Campaign::factory()
        ->for($group)
        ->for($user, 'creator')
        ->create([
            'title' => 'Shadowfall Chronicle',
            'synopsis' => 'The creeping shadow encroaches upon the Vale.',
        ]);

    $session = CampaignSession::factory()
        ->for($campaign)
        ->for($user, 'creator')
        ->create([
            'title' => 'Shadow Council',
            'agenda' => 'Discuss defenses against the shadow tide',
        ]);

    $note = SessionNote::factory()->create([
        'campaign_id' => $campaign->id,
        'campaign_session_id' => $session->id,
        'author_id' => $user->id,
        'content' => 'Recorded a whispered warning about the shadow gate.',
    ]);

    $task = CampaignTask::factory()
        ->for($campaign)
        ->create([
            'title' => 'Secure the Shadow Gate',
            'description' => 'Seal the rift before nightfall.',
        ]);

    // Foreign data that should not appear
    Campaign::factory()->create(['title' => 'Sunrise Covenant']);

    $results = $service->search($user, 'shadow');

    expect($results['campaigns'])->toHaveCount(1)
        ->and($results['campaigns'][0]['id'])->toBe($campaign->id);

    expect($results['sessions'])->toHaveCount(1)
        ->and($results['sessions'][0]['id'])->toBe($session->id);

    expect($results['notes'])->toHaveCount(1)
        ->and($results['notes'][0]['id'])->toBe($note->id);

    expect($results['tasks'])->toHaveCount(1)
        ->and($results['tasks'][0]['id'])->toBe($task->id);
});

it('hides gm-only notes from non-managers', function () {
    /** @var GlobalSearchService $service */
    $service = app(GlobalSearchService::class);

    $owner = User::factory()->create();
    $player = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $campaign = Campaign::factory()->for($group)->for($owner, 'creator')->create(['title' => 'Shattered Sigil']);

    SessionNote::factory()
        ->for($campaign)
        ->for($owner, 'author')
        ->create([
            'visibility' => SessionNote::VISIBILITY_GM,
            'content' => 'GM shadow directive.',
        ]);

    $results = $service->search($player, 'shadow');

    expect($results['notes'])->toBeEmpty();
});

it('shows gm-only notes to campaign managers', function () {
    /** @var GlobalSearchService $service */
    $service = app(GlobalSearchService::class);

    $owner = User::factory()->create();
    $manager = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_DUNGEON_MASTER,
    ]);

    $campaign = Campaign::factory()->for($group)->for($owner, 'creator')->create(['title' => 'Veiled Depths']);

    $note = SessionNote::factory()
        ->for($campaign)
        ->for($owner, 'author')
        ->create([
            'visibility' => SessionNote::VISIBILITY_GM,
            'content' => 'Shadow ritual instructions.',
        ]);

    $results = $service->search($manager, 'shadow');

    expect($results['notes'])
        ->toHaveCount(1)
        ->and($results['notes'][0]['id'])->toBe($note->id);
});
