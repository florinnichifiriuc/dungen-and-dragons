<?php

use App\Models\ConditionTimerSummaryShare;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows facilitators to generate shareable condition outlook links', function () {
    $manager = User::factory()->create();
    $group = Group::factory()->create([
        'created_by' => $manager->id,
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $map = Map::factory()->create([
        'group_id' => $group->id,
        'region_id' => null,
    ]);

    MapToken::factory()->create([
        'map_id' => $map->id,
        'status_conditions' => ['bless'],
        'status_condition_durations' => ['bless' => 3],
        'faction' => MapToken::FACTION_ALLIED,
        'hidden' => false,
    ]);

    $response = $this->actingAs($manager)
        ->post(route('groups.condition-timers.player-summary.share-links.store', $group));

    $response->assertRedirect();

    $share = ConditionTimerSummaryShare::query()->where('group_id', $group->id)->first();

    expect($share)->not->toBeNull();

    config()->set('app.asset_url', 'http://localhost/build');
    $version = hash('xxh128', config('app.asset_url'));

    $shareResponse = $this->get(
        route('shares.condition-timers.player-summary.show', $share->token),
        [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ],
    );

    $shareResponse->assertOk();
    $payload = $shareResponse->json();

    expect($payload['component'])->toBe('Shares/ConditionTimerSummary');
    expect(data_get($payload, 'props.share.created_at'))->not->toBeNull();
});

it('prevents outsiders from managing share links', function () {
    $manager = User::factory()->create();
    $group = Group::factory()->create([
        'created_by' => $manager->id,
    ]);

    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->post(route('groups.condition-timers.player-summary.share-links.store', $group))
        ->assertForbidden();

    expect(ConditionTimerSummaryShare::query()->exists())->toBeFalse();
});

it('expires share links when revoked or past their window', function () {
    $manager = User::factory()->create();
    $group = Group::factory()->create([
        'created_by' => $manager->id,
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $share = ConditionTimerSummaryShare::factory()->create([
        'group_id' => $group->id,
        'created_by' => $manager->id,
        'expires_at' => CarbonImmutable::now('UTC')->addDay(),
    ]);

    $this->actingAs($manager)
        ->delete(route('groups.condition-timers.player-summary.share-links.destroy', [$group, $share]))
        ->assertRedirect();

    expect($share->fresh()->deleted_at)->not()->toBeNull();

    config()->set('app.asset_url', 'http://localhost/build');
    $version = hash('xxh128', config('app.asset_url'));

    $this->get(
        route('shares.condition-timers.player-summary.show', $share->token),
        [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ],
    )
        ->assertNotFound();

    $expiredShare = ConditionTimerSummaryShare::factory()->create([
        'group_id' => $group->id,
        'created_by' => $manager->id,
        'expires_at' => CarbonImmutable::now('UTC')->subDay(),
    ]);

    $this->get(
        route('shares.condition-timers.player-summary.show', $expiredShare->token),
        [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ],
    )
        ->assertNotFound();
});
