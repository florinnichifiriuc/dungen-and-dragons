<?php

use App\Models\ConditionTimerShareConsentLog;
use App\Models\ConditionTimerSummaryShare;
use App\Models\ConditionTimerSummaryShareAccess;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows facilitators to generate shareable condition outlook links when players consent', function () {
    $manager = User::factory()->create();
    $group = Group::factory()->create([
        'created_by' => $manager->id,
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $player = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    ConditionTimerShareConsentLog::factory()->create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'recorded_by' => $manager->id,
        'action' => 'granted',
        'visibility' => 'details',
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
        ->post(route('groups.condition-timers.player-summary.share-links.store', $group), [
            'visibility_mode' => 'details',
        ]);

    $response->assertRedirect();

    $share = ConditionTimerSummaryShare::query()->where('group_id', $group->id)->first();

    expect($share)->not->toBeNull();
    expect($share->visibility_mode)->toBe('details');
    expect($share->consent_snapshot)->not->toBeNull();
    expect($share->consent_snapshot['granted_user_ids'] ?? [])->toContain($player->id);

    config()->set('app.asset_url', 'http://localhost/build');
    $version = hash('xxh128', config('app.asset_url'));

    $shareResponse = $this
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.77'])
        ->get(
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
    expect(data_get($payload, 'props.share.state.state'))->toBe('active');

    $access = ConditionTimerSummaryShareAccess::query()->first();

    expect($access)->not->toBeNull();
    expect($access->event_type)->toBe('access');
    expect($access->ip_hash)->not->toBe('203.0.113.77');
    expect($access->user_agent_hash)->not->toBeNull();
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

it('allows managers to extend share expiries and logs the change', function () {
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
        'expires_at' => CarbonImmutable::now('UTC')->addHours(6),
    ]);

    $this->actingAs($manager)
        ->patch(route('groups.condition-timers.player-summary.share-links.extend', [$group, $share]), [
            'expiry_preset' => '24h',
        ])
        ->assertRedirect();

    $share->refresh();

    expect($share->expires_at)->toBeInstanceOf(CarbonImmutable::class);
    expect($share->expires_at->diffInHours(CarbonImmutable::now('UTC')))->toBeGreaterThan(12);

    $extensionEvent = ConditionTimerSummaryShareAccess::query()
        ->where('condition_timer_summary_share_id', $share->id)
        ->where('event_type', 'extension')
        ->first();

    expect($extensionEvent)->not->toBeNull();
    expect($extensionEvent->metadata['expires_at'] ?? null)->toBe($share->expires_at?->toIso8601String());
});

it('redacts shares that have been expired for more than forty-eight hours', function () {
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
        'expires_at' => CarbonImmutable::now('UTC')->subDays(3),
    ]);

    config()->set('app.asset_url', 'http://localhost/build');
    $version = hash('xxh128', config('app.asset_url'));

    $response = $this->get(
        route('shares.condition-timers.player-summary.show', $share->token),
        [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ],
    );

    $response->assertOk();

    $payload = $response->json();

    expect(data_get($payload, 'props.summary.entries'))->toBeArray()->toBeEmpty();
    expect(data_get($payload, 'props.share.state.redacted'))->toBeTrue();

    $recentlyExpired = ConditionTimerSummaryShare::factory()->create([
        'group_id' => $group->id,
        'created_by' => $manager->id,
        'expires_at' => CarbonImmutable::now('UTC')->subHours(2),
    ]);

    $this->get(
        route('shares.condition-timers.player-summary.show', $recentlyExpired->token),
        [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ],
    )->assertNotFound();
});

it('requires player consent before issuing detailed share links', function () {
    $manager = User::factory()->create();
    $group = Group::factory()->create([
        'created_by' => $manager->id,
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $player = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $this->actingAs($manager)
        ->from(route('groups.condition-timers.player-summary', $group))
        ->post(route('groups.condition-timers.player-summary.share-links.store', $group), [
            'visibility_mode' => 'details',
        ])
        ->assertRedirect(route('groups.condition-timers.player-summary', $group))
        ->assertSessionHasErrors(['share']);

    expect(ConditionTimerSummaryShare::query()->exists())->toBeFalse();
});
