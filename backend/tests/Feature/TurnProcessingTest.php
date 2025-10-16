<?php

use App\Events\MapTokenBroadcasted;
use App\Models\AiRequest;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\Region;
use App\Models\Turn;
use App\Models\User;
use App\Models\World;
use App\Services\TurnScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('allows authorized managers to process region turns', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $world = World::factory()->for($group)->create();
    $region = Region::factory()->for($group)->for($world)->create();

    app(TurnScheduler::class)->configure($region, 24, CarbonImmutable::now('UTC'));

    $response = $this->actingAs($user)->post(route('groups.regions.turns.store', [$group, $region]), [
        'summary' => 'Expedition concluded with new alliances.',
    ]);

    $response->assertRedirect(route('groups.show', $group));

    $turn = Turn::where('region_id', $region->id)->first();

    expect($turn)->not->toBeNull();
    expect($turn->number)->toBe(1);
    expect($turn->summary)->toBe('Expedition concluded with new alliances.');

    $configuration = $region->fresh()->turnConfiguration;
    expect($configuration?->last_processed_at)->not->toBeNull();
    expect($configuration?->next_turn_at)->toBeInstanceOf(CarbonImmutable::class);
});

it('supports ai fallback when no summary is provided', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $world = World::factory()->for($group)->create();
    $region = Region::factory()->for($group)->for($world)->create();

    app(TurnScheduler::class)->configure($region, 6, CarbonImmutable::now('UTC'));

    Http::fake([
        '*/api/chat' => Http::response([
            'message' => ['content' => 'Stormbreak Vale thrived under vigilant spirits.'],
        ], 200),
    ]);

    $this->actingAs($user)->post(route('groups.regions.turns.store', [$group, $region]), [
        'use_ai_fallback' => true,
    ])->assertRedirect(route('groups.show', $group));

    $turn = Turn::where('region_id', $region->id)->firstOrFail();

    expect($turn->used_ai_fallback)->toBeTrue();
    expect($turn->summary)->toBe('Stormbreak Vale thrived under vigilant spirits.');

    $this->assertDatabaseHas('ai_requests', [
        'request_type' => 'summary',
        'context_type' => Region::class,
        'context_id' => $region->id,
        'status' => AiRequest::STATUS_COMPLETED,
    ]);
});

it('prevents players from processing region turns', function () {
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

    $world = World::factory()->for($group)->create();
    $region = Region::factory()->for($group)->for($world)->create();

    app(TurnScheduler::class)->configure($region, 24, CarbonImmutable::now('UTC'));

    $response = $this->actingAs($player)->post(route('groups.regions.turns.store', [$group, $region]), []);

    $response->assertForbidden();
    $this->assertDatabaseCount('turns', 0);
});

it('decrements token condition timers and clears expired conditions when a turn processes', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $world = World::factory()->for($group)->create();
    $region = Region::factory()->for($group)->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();

    $tokenWithTimers = MapToken::factory()->for($map)->create([
        'status_conditions' => ['frightened', 'poisoned'],
        'status_condition_durations' => ['frightened' => 1, 'poisoned' => 3],
    ]);

    $tokenWithoutTimers = MapToken::factory()->for($map)->create([
        'status_conditions' => ['blinded'],
        'status_condition_durations' => [],
    ]);

    Event::fake([MapTokenBroadcasted::class]);

    app(TurnScheduler::class)->configure($region, 24, CarbonImmutable::now('UTC'));

    $turn = app(TurnScheduler::class)->process($region, $user, 'Night patrol rotates to dawn watch.');

    expect($turn->number)->toBe(1);

    $tokenWithTimers->refresh();
    $tokenWithoutTimers->refresh();

    expect($tokenWithTimers->status_conditions)->toBe(['poisoned']);
    expect($tokenWithTimers->status_condition_durations)->toBe(['poisoned' => 2]);

    expect($tokenWithoutTimers->status_conditions)->toBe(['blinded']);
    expect($tokenWithoutTimers->status_condition_durations)->toBe([]);

    Event::assertDispatchedTimes(MapTokenBroadcasted::class, 1);
    Event::assertDispatched(MapTokenBroadcasted::class, function (MapTokenBroadcasted $event) use ($map, $tokenWithTimers) {
        expect($event->map->is($map))->toBeTrue();
        expect($event->action)->toBe('updated');
        expect($event->token['id'])->toBe($tokenWithTimers->id);
        expect($event->token['status_conditions'])->toBe(['poisoned']);
        expect($event->token['status_condition_durations'])->toBe(['poisoned' => 2]);

        return true;
    });
});
