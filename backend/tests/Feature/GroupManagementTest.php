<?php

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows owners to invite existing users into the group', function () {
    $owner = User::factory()->create();
    $target = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $response = $this->actingAs($owner)->post(route('groups.memberships.store', $group), [
        'email' => $target->email,
        'role' => GroupMembership::ROLE_DUNGEON_MASTER,
    ]);

    $response->assertRedirect(route('groups.show', $group));

    $this->assertDatabaseHas('group_memberships', [
        'group_id' => $group->id,
        'user_id' => $target->id,
        'role' => GroupMembership::ROLE_DUNGEON_MASTER,
    ]);
});

it('prevents dungeon masters from promoting a member to owner', function () {
    $owner = User::factory()->create();
    $dm = User::factory()->create();
    $target = User::factory()->create();

    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $dmMembership = GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $dm->id,
        'role' => GroupMembership::ROLE_DUNGEON_MASTER,
    ]);

    $targetMembership = GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $target->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $response = $this->actingAs($dm)->patch(route('groups.memberships.update', [$group, $targetMembership]), [
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('group_memberships', [
        'id' => $targetMembership->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);
    $this->assertDatabaseHas('group_memberships', [
        'id' => $dmMembership->id,
        'role' => GroupMembership::ROLE_DUNGEON_MASTER,
    ]);
});

it('blocks removing the final owner from the roster', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();

    $ownerMembership = GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $response = $this->actingAs($owner)->delete(route('groups.memberships.destroy', [$group, $ownerMembership]));

    $response->assertSessionHasErrors('membership');
    $this->assertDatabaseHas('group_memberships', [
        'id' => $ownerMembership->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);
});

it('allows members to leave when another owner remains', function () {
    $owner = User::factory()->create();
    $secondOwner = User::factory()->create();

    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $secondMembership = GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $secondOwner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $response = $this->actingAs($secondOwner)->delete(route('groups.memberships.destroy', [$group, $secondMembership]));

    $response->assertRedirect(route('groups.index'));
    $this->assertDatabaseMissing('group_memberships', [
        'id' => $secondMembership->id,
    ]);
});

it('lets adventurers join by code and avoids duplicates', function () {
    $owner = User::factory()->create();
    $joiner = User::factory()->create();

    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $joinResponse = $this->actingAs($joiner)->post(route('groups.join.store'), [
        'code' => strtolower($group->join_code),
    ]);

    $joinResponse->assertRedirect(route('groups.show', $group));

    $this->assertDatabaseHas('group_memberships', [
        'group_id' => $group->id,
        'user_id' => $joiner->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $repeatResponse = $this->actingAs($joiner)->post(route('groups.join.store'), [
        'code' => $group->join_code,
    ]);

    $repeatResponse->assertRedirect(route('groups.show', $group));
    $this->assertDatabaseCount('group_memberships', 2);
});

it('prevents players from inviting other members', function () {
    $owner = User::factory()->create();
    $player = User::factory()->create();
    $target = User::factory()->create();

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

    $response = $this->actingAs($player)->post(route('groups.memberships.store', $group), [
        'email' => $target->email,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('group_memberships', [
        'group_id' => $group->id,
        'user_id' => $target->id,
    ]);
});
