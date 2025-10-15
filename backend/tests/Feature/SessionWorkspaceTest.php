<?php

use App\Events\DiceRollBroadcasted;
use App\Events\InitiativeEntryBroadcasted;
use App\Events\SessionNoteBroadcasted;
use App\Models\Campaign;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\CampaignSession;
use App\Models\SessionNote;
use App\Models\User;
use App\Services\DiceRoller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

afterEach(function (): void {
    \Mockery::close();
});

function seedCampaignWithManager(): array
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

it('allows managers to schedule sessions', function () {
    [$manager, $campaign] = seedCampaignWithManager();

    $response = $this->actingAs($manager)->post(route('campaigns.sessions.store', [
        'campaign' => $campaign,
    ]), [
        'title' => 'War Council',
        'session_date' => '2025-10-15T18:00',
        'duration_minutes' => 120,
        'location' => 'Discord',
    ]);

    $response->assertRedirect(route('campaigns.sessions.show', [$campaign, CampaignSession::first()]));

    $this->assertDatabaseHas('campaign_sessions', [
        'title' => 'War Council',
        'campaign_id' => $campaign->id,
    ]);
});

it('prevents players from scheduling sessions', function () {
    [$manager, $campaign, $group] = seedCampaignWithManager();

    $player = User::factory()->create();
    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $this->actingAs($player)
        ->post(route('campaigns.sessions.store', ['campaign' => $campaign]), [
            'title' => 'Unauthorized summit',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('campaign_sessions', 0);
});

it('allows party members to add notes while restricting gm-only visibility', function () {
    [$manager, $campaign] = seedCampaignWithManager();
    $session = CampaignSession::factory()->for($campaign)->create([
        'created_by' => $manager->id,
    ]);

    $player = User::factory()->create();
    GroupMembership::create([
        'group_id' => $campaign->group_id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    Event::fake([SessionNoteBroadcasted::class]);

    $gmAttempt = $this->actingAs($player)->from(route('campaigns.sessions.show', [$campaign, $session]))->post(
        route('campaigns.sessions.notes.store', ['campaign' => $campaign, 'session' => $session]),
        [
            'content' => 'Secret plot twist',
            'visibility' => SessionNote::VISIBILITY_GM,
        ],
    );

    $gmAttempt->assertSessionHasErrors('visibility');

    $this->actingAs($player)
        ->post(route('campaigns.sessions.notes.store', ['campaign' => $campaign, 'session' => $session]), [
            'content' => 'Battle plans ready.',
            'visibility' => SessionNote::VISIBILITY_PLAYERS,
        ])
        ->assertRedirect(route('campaigns.sessions.show', [$campaign, $session]));

    $this->assertDatabaseHas('session_notes', [
        'content' => 'Battle plans ready.',
        'visibility' => SessionNote::VISIBILITY_PLAYERS,
        'campaign_session_id' => $session->id,
    ]);

    Event::assertDispatched(SessionNoteBroadcasted::class, function (SessionNoteBroadcasted $event) use ($session) {
        expect($event->session->is($session))->toBeTrue();
        expect($event->action)->toBe('created');
        expect($event->note['content'])->toBe('Battle plans ready.');

        return true;
    });
    Event::assertDispatchedTimes(SessionNoteBroadcasted::class, 1);
});

it('records dice rolls using the dice roller service', function () {
    [$manager, $campaign] = seedCampaignWithManager();
    $session = CampaignSession::factory()->for($campaign)->create([
        'created_by' => $manager->id,
    ]);

    $mock = \Mockery::mock(DiceRoller::class);
    $mock->shouldReceive('roll')->once()->with('1D20+5')->andReturn([
        'rolls' => [12],
        'modifier' => 5,
        'total' => 17,
    ]);

    $this->instance(DiceRoller::class, $mock);

    Event::fake([DiceRollBroadcasted::class]);

    $this->actingAs($manager)
        ->post(route('campaigns.sessions.dice-rolls.store', ['campaign' => $campaign, 'session' => $session]), [
            'expression' => '1d20+5',
        ])
        ->assertRedirect(route('campaigns.sessions.show', [$campaign, $session]));

    $this->assertDatabaseHas('dice_rolls', [
        'campaign_session_id' => $session->id,
        'expression' => '1D20+5',
        'result_total' => 17,
    ]);

    Event::assertDispatched(DiceRollBroadcasted::class, function (DiceRollBroadcasted $event) use ($session) {
        expect($event->session->is($session))->toBeTrue();
        expect($event->action)->toBe('created');
        expect($event->roll['result_total'])->toBe(17);

        return true;
    });
    Event::assertDispatchedTimes(DiceRollBroadcasted::class, 1);
});

it('restricts initiative management to campaign managers', function () {
    [$manager, $campaign, $group] = seedCampaignWithManager();
    $session = CampaignSession::factory()->for($campaign)->create([
        'created_by' => $manager->id,
    ]);

    $mock = \Mockery::mock(DiceRoller::class);
    $mock->shouldReceive('roll')->andReturn([
        'rolls' => [10],
        'modifier' => 2,
        'total' => 12,
    ]);
    $this->instance(DiceRoller::class, $mock);

    Event::fake([InitiativeEntryBroadcasted::class]);

    $this->actingAs($manager)
        ->post(route('campaigns.sessions.initiative.store', ['campaign' => $campaign, 'session' => $session]), [
            'name' => 'Sir Roland',
            'dexterity_mod' => 2,
        ])
        ->assertRedirect(route('campaigns.sessions.show', [$campaign, $session]));

    $this->assertDatabaseHas('initiative_entries', [
        'campaign_session_id' => $session->id,
        'name' => 'Sir Roland',
        'initiative' => 12,
    ]);

    Event::assertDispatched(InitiativeEntryBroadcasted::class, function (InitiativeEntryBroadcasted $event) use ($session) {
        expect($event->session->is($session))->toBeTrue();
        expect($event->action)->toBe('created');
        expect($event->entries)->not->toBeEmpty();

        return true;
    });
    Event::assertDispatchedTimes(InitiativeEntryBroadcasted::class, 1);

    $player = User::factory()->create();
    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $this->actingAs($player)
        ->post(route('campaigns.sessions.initiative.store', ['campaign' => $campaign, 'session' => $session]), [
            'name' => 'Sneaky Player',
            'dexterity_mod' => 1,
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('initiative_entries', 1);
});
