<?php

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapTile;
use App\Models\Region;
use App\Models\TileTemplate;
use App\Models\User;
use App\Models\World;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createFogGroupWithOwner(): array
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

it('allows builders to hide and reveal tiles from players', function () {
    [$group, $owner] = createFogGroupWithOwner();
    $world = World::factory()->for($group)->create();
    $region = Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();
    $template = TileTemplate::factory()->for($group)->create();
    $tile = MapTile::factory()->for($map)->for($template, 'tileTemplate')->create();

    $response = $this->actingAs($owner)->put(route('groups.maps.fog.update', [$group, $map]), [
        'hidden_tile_ids' => [$tile->id],
    ]);

    $response->assertRedirect(route('groups.maps.show', [$group, $map]));

    $map->refresh();
    expect($map->fog_data)->toMatchArray([
        'hidden_tile_ids' => [$tile->id],
    ]);

    $reveal = $this->actingAs($owner)->put(route('groups.maps.fog.update', [$group, $map]), [
        'hidden_tile_ids' => [],
    ]);

    $reveal->assertRedirect(route('groups.maps.show', [$group, $map]));

    $map->refresh();
    expect($map->fog_data)->toBeNull();
});

it('rejects fog updates that reference tiles from another map', function () {
    [$group, $owner] = createFogGroupWithOwner();
    $world = World::factory()->for($group)->create();
    $region = Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();
    $template = TileTemplate::factory()->for($group)->create();
    $tile = MapTile::factory()->for($map)->for($template, 'tileTemplate')->create();

    $otherMap = Map::factory()->for($group)->for($region)->create();
    $foreignTile = MapTile::factory()->for($otherMap)->for($template, 'tileTemplate')->create();

    $response = $this
        ->actingAs($owner)
        ->from(route('groups.maps.show', [$group, $map]))
        ->put(route('groups.maps.fog.update', [$group, $map]), [
            'hidden_tile_ids' => [$tile->id, $foreignTile->id],
        ]);

    $response->assertSessionHasErrors('hidden_tile_ids.1');

    $map->refresh();
    expect($map->fog_data)->toBeNull();
});

it('prevents players from modifying fog of war', function () {
    [$group, $owner] = createFogGroupWithOwner();
    $player = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $world = World::factory()->for($group)->create();
    $region = Region::factory()->for($world)->create();
    $map = Map::factory()->for($group)->for($region)->create();
    $template = TileTemplate::factory()->for($group)->create();
    $tile = MapTile::factory()->for($map)->for($template, 'tileTemplate')->create();

    $response = $this->actingAs($player)->put(route('groups.maps.fog.update', [$group, $map]), [
        'hidden_tile_ids' => [$tile->id],
    ]);

    $response->assertForbidden();
    $map->refresh();
    expect($map->fog_data)->toBeNull();
});
