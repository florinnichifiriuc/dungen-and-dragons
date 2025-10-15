<?php

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Region;
use App\Models\Turn;
use App\Models\User;
use App\Models\World;
use App\Services\TurnScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    $this->actingAs($user)->post(route('groups.regions.turns.store', [$group, $region]), [
        'use_ai_fallback' => true,
    ])->assertRedirect(route('groups.show', $group));

    $turn = Turn::where('region_id', $region->id)->firstOrFail();

    expect($turn->used_ai_fallback)->toBeTrue();
    expect($turn->summary)->not->toBeNull();
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
