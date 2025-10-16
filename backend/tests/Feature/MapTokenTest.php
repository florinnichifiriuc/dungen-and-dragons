<?php

use App\Events\MapTokenBroadcasted;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\User;
use App\Models\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function createGroupWithOwnerForTokens(): array
{
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    return [$group, $owner];
}

it('allows dungeon masters to place tokens and broadcasts the payload', function () {
    [$group, $owner] = createGroupWithOwnerForTokens();
    $dm = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $dm->id,
        'role' => GroupMembership::ROLE_DUNGEON_MASTER,
    ]);

    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();

    Event::fake([MapTokenBroadcasted::class]);

    $response = $this->actingAs($dm)->post(route('groups.maps.tokens.store', [$group, $map]), [
        'name' => 'Red Dragon',
        'x' => 4,
        'y' => -2,
        'color' => '#dc2626',
        'size' => 'huge',
        'faction' => MapToken::FACTION_HOSTILE,
        'initiative' => 21,
        'status_effects' => 'Blessed by Tiamat',
        'status_conditions' => ['frightened', 'poisoned'],
        'status_condition_durations' => ['frightened' => 3, 'poisoned' => 2],
        'hit_points' => 187,
        'temporary_hit_points' => 12,
        'max_hit_points' => 220,
        'z_index' => 8,
        'hidden' => true,
        'gm_note' => 'Breath weapon recharging on 5-6.',
    ]);

    $response->assertRedirect(route('groups.maps.show', [$group, $map]));

    $this->assertDatabaseHas('map_tokens', [
        'map_id' => $map->id,
        'name' => 'Red Dragon',
        'x' => 4,
        'y' => -2,
        'color' => '#dc2626',
        'size' => 'huge',
        'faction' => MapToken::FACTION_HOSTILE,
        'initiative' => 21,
        'status_effects' => 'Blessed by Tiamat',
        'status_conditions' => json_encode(['frightened', 'poisoned']),
        'status_condition_durations' => json_encode(['frightened' => 3, 'poisoned' => 2]),
        'hit_points' => 187,
        'temporary_hit_points' => 12,
        'max_hit_points' => 220,
        'z_index' => 8,
        'hidden' => true,
    ]);

    Event::assertDispatched(MapTokenBroadcasted::class, function (MapTokenBroadcasted $event) use ($map) {
        expect($event->map->is($map))->toBeTrue();
        expect($event->action)->toBe('created');
        expect($event->token['name'])->toBe('Red Dragon');
        expect($event->token['faction'])->toBe(MapToken::FACTION_HOSTILE);
        expect($event->token['initiative'])->toBe(21);
        expect($event->token['status_effects'])->toBe('Blessed by Tiamat');
        expect($event->token['status_conditions'])->toBe(['frightened', 'poisoned']);
        expect($event->token['status_condition_durations'])->toBe(['frightened' => 3, 'poisoned' => 2]);
        expect($event->token['hit_points'])->toBe(187);
        expect($event->token['temporary_hit_points'])->toBe(12);
        expect($event->token['max_hit_points'])->toBe(220);
        expect($event->token['z_index'])->toBe(8);
        expect($event->token['hidden'])->toBeTrue();

        return true;
    });
});

it('prevents players from updating tokens', function () {
    [$group, $owner] = createGroupWithOwnerForTokens();
    $player = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();

    $token = MapToken::factory()->for($map)->create([
        'name' => 'Scout',
        'hidden' => false,
    ]);

    $response = $this->actingAs($player)->patch(route('groups.maps.tokens.update', [$group, $map, $token]), [
        'hidden' => true,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('map_tokens', [
        'id' => $token->id,
        'hidden' => false,
    ]);
});

it('allows owners to adjust token coordinates and notes', function () {
    [$group, $owner] = createGroupWithOwnerForTokens();
    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();

    $token = MapToken::factory()->for($map)->create([
        'name' => 'Rogue',
        'x' => 0,
        'y' => 0,
        'faction' => MapToken::FACTION_HOSTILE,
        'gm_note' => null,
    ]);

    Event::fake([MapTokenBroadcasted::class]);

    $response = $this->actingAs($owner)->patch(route('groups.maps.tokens.update', [$group, $map, $token]), [
        'x' => 7,
        'y' => 3,
        'initiative' => 15,
        'status_effects' => 'Restrained by vines',
        'status_conditions' => ['restrained', 'blinded'],
        'status_condition_durations' => ['restrained' => 4, 'blinded' => 1],
        'hit_points' => -3,
        'temporary_hit_points' => 5,
        'max_hit_points' => 45,
        'z_index' => -2,
        'faction' => MapToken::FACTION_ALLIED,
        'gm_note' => 'Prepared action to strike the mage.',
    ]);

    $response->assertRedirect(route('groups.maps.show', [$group, $map]));

    $this->assertDatabaseHas('map_tokens', [
        'id' => $token->id,
        'x' => 7,
        'y' => 3,
        'initiative' => 15,
        'status_effects' => 'Restrained by vines',
        'status_conditions' => json_encode(['blinded', 'restrained']),
        'status_condition_durations' => json_encode(['blinded' => 1, 'restrained' => 4]),
        'hit_points' => -3,
        'temporary_hit_points' => 5,
        'max_hit_points' => 45,
        'z_index' => -2,
        'faction' => MapToken::FACTION_ALLIED,
        'gm_note' => 'Prepared action to strike the mage.',
    ]);

    Event::assertDispatched(MapTokenBroadcasted::class, function (MapTokenBroadcasted $event) use ($map, $token) {
        expect($event->map->is($map))->toBeTrue();
        expect($event->action)->toBe('updated');
        expect($event->token['id'])->toBe($token->id);
        expect($event->token['x'])->toBe(7);
        expect($event->token['faction'])->toBe(MapToken::FACTION_ALLIED);
        expect($event->token['initiative'])->toBe(15);
        expect($event->token['hit_points'])->toBe(-3);
        expect($event->token['status_conditions'])->toBe(['blinded', 'restrained']);
        expect($event->token['status_condition_durations'])->toBe(['blinded' => 1, 'restrained' => 4]);
        expect($event->token['temporary_hit_points'])->toBe(5);
        expect($event->token['max_hit_points'])->toBe(45);
        expect($event->token['z_index'])->toBe(-2);

        return true;
    });
});

it('allows owners to clear individual condition timers from tokens', function () {
    [$group, $owner] = createGroupWithOwnerForTokens();
    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();

    $token = MapToken::factory()->for($map)->create([
        'name' => 'Battlefield Cleric',
        'status_conditions' => ['blinded', 'restrained'],
        'status_condition_durations' => ['blinded' => 1, 'restrained' => 4],
    ]);

    Event::fake([MapTokenBroadcasted::class]);

    $response = $this->actingAs($owner)->patch(
        route('groups.maps.tokens.update', [$group, $map, $token]),
        [
            'status_conditions' => ['restrained'],
            'status_condition_durations' => ['restrained' => 4],
        ],
    );

    $response->assertRedirect(route('groups.maps.show', [$group, $map]));

    $this->assertDatabaseHas('map_tokens', [
        'id' => $token->id,
        'status_conditions' => json_encode(['restrained']),
        'status_condition_durations' => json_encode(['restrained' => 4]),
    ]);

    Event::assertDispatched(MapTokenBroadcasted::class, function (MapTokenBroadcasted $event) use ($map, $token) {
        expect($event->map->is($map))->toBeTrue();
        expect($event->token['id'])->toBe($token->id);
        expect($event->token['status_conditions'])->toBe(['restrained']);
        expect($event->token['status_condition_durations'])->toBe(['restrained' => 4]);

        return true;
    });
});

it('normalizes condition duration payloads to active presets within bounds', function () {
    [$group, $owner] = createGroupWithOwnerForTokens();
    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();

    $token = MapToken::factory()->for($map)->create([
        'status_conditions' => ['frightened'],
        'status_condition_durations' => ['frightened' => 4],
    ]);

    Event::fake([MapTokenBroadcasted::class]);

    $response = $this->actingAs($owner)->patch(route('groups.maps.tokens.update', [$group, $map, $token]), [
        'status_conditions' => ['charmed'],
        'status_condition_durations' => [
            'charmed' => 18,
            'frightened' => 6,
            'poisoned' => 0,
        ],
    ]);

    $response->assertRedirect(route('groups.maps.show', [$group, $map]));

    $this->assertDatabaseHas('map_tokens', [
        'id' => $token->id,
        'status_conditions' => json_encode(['charmed']),
        'status_condition_durations' => json_encode(['charmed' => 18]),
    ]);

    Event::assertDispatched(MapTokenBroadcasted::class, function (MapTokenBroadcasted $event) use ($map, $token) {
        expect($event->map->is($map))->toBeTrue();
        expect($event->token['id'])->toBe($token->id);
        expect($event->token['status_conditions'])->toBe(['charmed']);
        expect($event->token['status_condition_durations'])->toBe(['charmed' => 18]);

        return true;
    });
});

it('allows facilitators to clear timers while keeping presets active', function () {
    [$group, $owner] = createGroupWithOwnerForTokens();
    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();

    $token = MapToken::factory()->for($map)->create([
        'status_conditions' => ['poisoned'],
        'status_condition_durations' => ['poisoned' => 3],
    ]);

    Event::fake([MapTokenBroadcasted::class]);

    $response = $this->actingAs($owner)->patch(route('groups.maps.tokens.update', [$group, $map, $token]), [
        'status_conditions' => ['poisoned'],
        'status_condition_durations' => null,
    ]);

    $response->assertRedirect(route('groups.maps.show', [$group, $map]));

    $this->assertDatabaseHas('map_tokens', [
        'id' => $token->id,
        'status_conditions' => json_encode(['poisoned']),
        'status_condition_durations' => null,
    ]);

    $token->refresh();

    expect($token->status_conditions)->toBe(['poisoned']);
    expect($token->status_condition_durations)->toBe([]);

    Event::assertDispatched(MapTokenBroadcasted::class, function (MapTokenBroadcasted $event) use ($map, $token) {
        expect($event->map->is($map))->toBeTrue();
        expect($event->token['id'])->toBe($token->id);
        expect($event->token['status_conditions'])->toBe(['poisoned']);
        expect($event->token['status_condition_durations'])->toBe([]);

        return true;
    });
});

it('allows dungeon masters to remove tokens with a broadcast payload', function () {
    [$group, $owner] = createGroupWithOwnerForTokens();
    $dm = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $dm->id,
        'role' => GroupMembership::ROLE_DUNGEON_MASTER,
    ]);

    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();

    $token = MapToken::factory()->for($map)->create();

    Event::fake([MapTokenBroadcasted::class]);

    $response = $this->actingAs($dm)->delete(route('groups.maps.tokens.destroy', [$group, $map, $token]));

    $response->assertRedirect(route('groups.maps.show', [$group, $map]));
    $this->assertDatabaseMissing('map_tokens', [
        'id' => $token->id,
    ]);

    Event::assertDispatched(MapTokenBroadcasted::class, function (MapTokenBroadcasted $event) use ($map, $token) {
        expect($event->map->is($map))->toBeTrue();
        expect($event->action)->toBe('deleted');
        expect($event->token['id'])->toBe($token->id);

        return true;
    });
});

it('allows encounter builders to clear initiative and status metadata', function () {
    [$group, $owner] = createGroupWithOwnerForTokens();
    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();

    $token = MapToken::factory()->for($map)->create([
        'initiative' => 12,
        'status_effects' => 'Paralyzed',
        'status_conditions' => ['paralyzed'],
        'hit_points' => 3,
        'temporary_hit_points' => 7,
        'max_hit_points' => 25,
        'z_index' => 4,
    ]);

    $response = $this->actingAs($owner)->patch(route('groups.maps.tokens.update', [$group, $map, $token]), [
        'initiative' => null,
        'status_effects' => '',
        'status_conditions' => [],
        'hit_points' => '',
        'temporary_hit_points' => '',
        'max_hit_points' => '',
        'z_index' => '',
    ]);

    $response->assertRedirect(route('groups.maps.show', [$group, $map]));

    $this->assertDatabaseHas('map_tokens', [
        'id' => $token->id,
        'initiative' => null,
        'status_effects' => null,
        'status_conditions' => null,
        'hit_points' => null,
        'temporary_hit_points' => null,
        'max_hit_points' => null,
        'z_index' => 0,
    ]);
});

it('normalizes blank faction submissions back to neutral', function () {
    [$group, $owner] = createGroupWithOwnerForTokens();
    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();

    $token = MapToken::factory()->for($map)->create([
        'faction' => MapToken::FACTION_HOSTILE,
    ]);

    Event::fake([MapTokenBroadcasted::class]);

    $response = $this->actingAs($owner)->patch(route('groups.maps.tokens.update', [$group, $map, $token]), [
        'faction' => '',
    ]);

    $response->assertRedirect(route('groups.maps.show', [$group, $map]));

    $this->assertDatabaseHas('map_tokens', [
        'id' => $token->id,
        'faction' => MapToken::FACTION_NEUTRAL,
    ]);

    Event::assertDispatched(MapTokenBroadcasted::class, function (MapTokenBroadcasted $event) use ($token) {
        expect($event->token['id'])->toBe($token->id);
        expect($event->token['faction'])->toBe(MapToken::FACTION_NEUTRAL);

        return true;
    });
});

it('defaults new tokens to layer zero when not supplied', function () {
    [$group, $owner] = createGroupWithOwnerForTokens();
    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();

    Event::fake([MapTokenBroadcasted::class]);

    $response = $this->actingAs($owner)->post(route('groups.maps.tokens.store', [$group, $map]), [
        'name' => 'Scout',
        'x' => 1,
        'y' => 2,
        'size' => 'small',
    ]);

    $response->assertRedirect(route('groups.maps.show', [$group, $map]));

    $this->assertDatabaseHas('map_tokens', [
        'map_id' => $map->id,
        'name' => 'Scout',
        'z_index' => 0,
        'faction' => MapToken::FACTION_NEUTRAL,
    ]);

    Event::assertDispatched(MapTokenBroadcasted::class, function (MapTokenBroadcasted $event): bool {
        expect($event->token['z_index'])->toBe(0);
        expect($event->token['faction'])->toBe(MapToken::FACTION_NEUTRAL);

        return true;
    });
});
