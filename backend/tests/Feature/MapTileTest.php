<?php

use App\Events\MapTileBroadcasted;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapTile;
use App\Models\TileTemplate;
use App\Models\User;
use App\Models\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function createGroupWithOwner(): array
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

it('enforces unique coordinates per map', function () {
    [$group, $owner] = createGroupWithOwner();
    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();
    $template = TileTemplate::factory()->for($group)->create();

    Event::fake([MapTileBroadcasted::class]);

    $first = $this->actingAs($owner)->post(route('groups.maps.tiles.store', [$group, $map]), [
        'tile_template_id' => $template->id,
        'q' => 1,
        'r' => 2,
        'elevation' => 0,
    ]);

    $first->assertRedirect(route('groups.maps.show', [$group, $map]));

    Event::assertDispatched(MapTileBroadcasted::class, function (MapTileBroadcasted $event) use ($map) {
        expect($event->map->is($map))->toBeTrue();
        expect($event->action)->toBe('created');
        expect($event->tile['q'])->toBe(1);
        expect($event->tile['r'])->toBe(2);

        return true;
    });
    Event::assertDispatchedTimes(MapTileBroadcasted::class, 1);

    $second = $this->actingAs($owner)->post(route('groups.maps.tiles.store', [$group, $map]), [
        'tile_template_id' => $template->id,
        'q' => 1,
        'r' => 2,
        'elevation' => 1,
    ]);

    $second->assertStatus(422);
    $this->assertDatabaseCount('map_tiles', 1);
});

it('rejects tile placements using templates from another group', function () {
    [$group, $owner] = createGroupWithOwner();
    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();

    $foreignTemplate = TileTemplate::factory()->create();

    $response = $this->actingAs($owner)->post(route('groups.maps.tiles.store', [$group, $map]), [
        'tile_template_id' => $foreignTemplate->id,
        'q' => 0,
        'r' => 0,
    ]);

    $response->assertSessionHasErrors('tile_template_id');
});

it('allows owners to toggle lock state on tiles', function () {
    [$group, $owner] = createGroupWithOwner();
    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();
    $template = TileTemplate::factory()->for($group)->create();

    $tile = MapTile::factory()->for($map)->for($template, 'tileTemplate')->create([
        'locked' => false,
    ]);

    expect($tile->map_id)->toBe($map->id);
    expect($owner->can('update', $tile))->toBeTrue();

    Event::fake([MapTileBroadcasted::class]);

    $response = $this->actingAs($owner)->patch(route('groups.maps.tiles.update', [$group->id, $map->id, $tile->id]), [
        'locked' => true,
    ]);

    $response->assertRedirect(route('groups.maps.show', [$group, $map]));
    $this->assertDatabaseHas('map_tiles', [
        'id' => $tile->id,
        'locked' => true,
    ]);

    Event::assertDispatched(MapTileBroadcasted::class, function (MapTileBroadcasted $event) use ($map, $tile) {
        expect($event->map->is($map))->toBeTrue();
        expect($event->action)->toBe('updated');
        expect($event->tile['id'])->toBe($tile->id);

        return true;
    });
    Event::assertDispatchedTimes(MapTileBroadcasted::class, 1);
});

it('allows dungeon masters to update tile metadata', function () {
    [$group, $owner] = createGroupWithOwner();
    $dm = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $dm->id,
        'role' => GroupMembership::ROLE_DUNGEON_MASTER,
    ]);

    $world = World::factory()->for($group)->create();
    $region = \App\Models\Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();

    $templateA = TileTemplate::factory()->for($group)->create();
    $templateB = TileTemplate::factory()->for($group)->create();

    $tile = MapTile::factory()->for($map)->for($templateA, 'tileTemplate')->create([
        'q' => -1,
        'r' => 0,
        'elevation' => 0,
        'locked' => false,
    ]);

    expect($tile->map_id)->toBe($map->id);
    expect($dm->can('update', $tile))->toBeTrue();

    Event::fake([MapTileBroadcasted::class]);

    $response = $this->actingAs($dm)->patch(route('groups.maps.tiles.update', [$group->id, $map->id, $tile->id]), [
        'tile_template_id' => $templateB->id,
        'elevation' => 3,
        'variant' => json_encode(['hazard' => 'acid fog']),
    ]);

    $response->assertRedirect(route('groups.maps.show', [$group, $map]));

    $this->assertDatabaseHas('map_tiles', [
        'id' => $tile->id,
        'tile_template_id' => $templateB->id,
        'elevation' => 3,
    ]);

    Event::assertDispatched(MapTileBroadcasted::class, function (MapTileBroadcasted $event) use ($map, $tile) {
        expect($event->map->is($map))->toBeTrue();
        expect($event->action)->toBe('updated');
        expect($event->tile['id'])->toBe($tile->id);

        return true;
    });
    Event::assertDispatchedTimes(MapTileBroadcasted::class, 1);
});
