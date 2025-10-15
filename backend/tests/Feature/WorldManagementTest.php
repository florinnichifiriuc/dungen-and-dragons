<?php

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Region;
use App\Models\User;
use App\Models\World;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows owners to create worlds for their group', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $response = $this->actingAs($owner)->post(route('groups.worlds.store', $group), [
        'name' => 'Eclipsed Horizons',
        'summary' => 'Fragments of moonlight stitched into a sea of stars.',
        'description' => 'An interwoven realm curated by the Dawnstriders.',
        'default_turn_duration_hours' => 24,
    ]);

    $response->assertRedirect(route('groups.show', $group));

    $this->assertDatabaseHas('worlds', [
        'group_id' => $group->id,
        'name' => 'Eclipsed Horizons',
        'default_turn_duration_hours' => 24,
    ]);
});

it('allows dungeon masters to update world metadata', function () {
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

    $world = World::factory()->for($group)->create([
        'name' => 'Crimson Meridian',
        'default_turn_duration_hours' => 12,
    ]);

    $response = $this->actingAs($dm)->put(route('groups.worlds.update', [$group, $world]), [
        'name' => 'Crimson Meridian',
        'summary' => 'Sunswept ley-lines spanning the frontier.',
        'description' => 'New lore etched by the DM council.',
        'default_turn_duration_hours' => 18,
    ]);

    $response->assertRedirect(route('groups.show', $group));

    $this->assertDatabaseHas('worlds', [
        'id' => $world->id,
        'default_turn_duration_hours' => 18,
        'summary' => 'Sunswept ley-lines spanning the frontier.',
    ]);
});

it('blocks deleting worlds that still have regions', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $world = World::factory()->for($group)->create();
    Region::factory()->for($group)->for($world)->create();

    $response = $this->actingAs($owner)->delete(route('groups.worlds.destroy', [$group, $world]));

    $response->assertStatus(422);
    $this->assertDatabaseHas('worlds', ['id' => $world->id]);
});

it('allows owners to delete empty worlds', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $world = World::factory()->for($group)->create();

    $response = $this->actingAs($owner)->delete(route('groups.worlds.destroy', [$group, $world]));

    $response->assertRedirect(route('groups.show', $group));
    $this->assertDatabaseMissing('worlds', ['id' => $world->id]);
});

it('requires regions to target a world that belongs to the group', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();
    $otherGroup = Group::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $foreignWorld = World::factory()->for($otherGroup)->create();

    $response = $this->actingAs($owner)->post(route('groups.regions.store', $group), [
        'name' => 'Borrowed Vale',
        'summary' => 'Illicit region attempt.',
        'description' => 'Should not be created.',
        'world_id' => $foreignWorld->id,
        'turn_duration_hours' => 24,
    ]);

    $response->assertNotFound();
    $this->assertDatabaseMissing('regions', ['name' => 'Borrowed Vale']);
});
