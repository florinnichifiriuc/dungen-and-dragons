<?php

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapTile;
use App\Models\TileTemplate;
use App\Models\User;
use App\Models\World;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows dungeon masters to create tile templates for their group', function () {
    $owner = User::factory()->create();
    $dm = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();
    $world = World::factory()->for($group)->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $dm->id,
        'role' => GroupMembership::ROLE_DUNGEON_MASTER,
    ]);

    $response = $this->actingAs($dm)->post(route('groups.tile-templates.store', $group), [
        'name' => 'Crystal Plains',
        'key' => 'crystal-plains',
        'terrain_type' => 'crystal',
        'movement_cost' => 2,
        'defense_bonus' => 1,
        'world_id' => $world->id,
        'edge_profile' => json_encode(['north' => 'road']),
    ]);

    $response->assertRedirect(route('groups.show', $group));

    $this->assertDatabaseHas('tile_templates', [
        'group_id' => $group->id,
        'name' => 'Crystal Plains',
        'world_id' => $world->id,
    ]);
});

it('prevents deleting tile templates that are still referenced by tiles', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();
    $region = \App\Models\Region::factory()->for($group)->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $template = TileTemplate::factory()->for($group)->create([
        'world_id' => null,
    ]);

    $map = Map::factory()->for($group)->for($region)->create();
    MapTile::factory()->for($map)->for($template, 'tileTemplate')->create([
        'q' => 0,
        'r' => 0,
    ]);

    $response = $this->actingAs($owner)->delete(route('groups.tile-templates.destroy', [$group, $template]));

    $response->assertStatus(422);
    $this->assertDatabaseHas('tile_templates', ['id' => $template->id]);
});

it('allows owners to delete unused tile templates', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $template = TileTemplate::factory()->for($group)->create();

    $response = $this->actingAs($owner)->delete(route('groups.tile-templates.destroy', [$group, $template]));

    $response->assertRedirect(route('groups.show', $group));
    $this->assertDatabaseMissing('tile_templates', ['id' => $template->id]);
});

it('blocks dungeon masters from deleting templates directly', function () {
    $owner = User::factory()->create();
    $dm = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $dm->id,
        'role' => GroupMembership::ROLE_DUNGEON_MASTER,
    ]);

    $template = TileTemplate::factory()->for($group)->create();

    $response = $this->actingAs($dm)->delete(route('groups.tile-templates.destroy', [$group, $template]));

    $response->assertForbidden();
    $this->assertDatabaseHas('tile_templates', ['id' => $template->id]);
});
