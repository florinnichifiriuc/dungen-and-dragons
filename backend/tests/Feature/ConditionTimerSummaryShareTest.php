<?php

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

    expect(ConditionTimerSummaryShareAccess::query()->count())->toBe(1);
    expect(data_get($payload, 'props.share.stats.total_views'))->toBe(1);
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

it('surfaces access statistics to facilitators', function () {
    $manager = User::factory()->create();
    $group = Group::factory()->create([
        'created_by' => $manager->id,
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-11-05 12:00:00', 'UTC'));

    /** @var \App\Services\ConditionTimerSummaryShareService $shareService */
    $shareService = app(\App\Services\ConditionTimerSummaryShareService::class);
    $share = $shareService->createShareForGroup($group, $manager);

    $share->accessLogs()->create([
        'accessed_at' => CarbonImmutable::now('UTC')->subDays(1)->setTime(9, 0),
        'ip_address' => '192.0.2.15',
        'user_agent' => 'Diviner HUD',
    ]);

    $share->accessLogs()->create([
        'accessed_at' => CarbonImmutable::now('UTC')->subDays(3)->setTime(22, 30),
        'ip_address' => '198.51.100.75',
        'user_agent' => 'Sending Stone',
    ]);

    $share->accessLogs()->create([
        'accessed_at' => CarbonImmutable::now('UTC')->subDays(8),
        'ip_address' => '203.0.113.50',
        'user_agent' => 'Ancient Relic Browser',
    ]);

    config()->set('app.asset_url', 'http://localhost/build');
    $version = hash('xxh128', config('app.asset_url'));

    $this->withServerParameters([
        'REMOTE_ADDR' => '203.0.113.10',
        'HTTP_USER_AGENT' => 'Mage Tower Tablet',
    ])->get(
        route('shares.condition-timers.player-summary.show', $share->token),
        [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ],
    )->assertOk();

    $this->withServerParameters([
        'REMOTE_ADDR' => '198.51.100.24',
        'HTTP_USER_AGENT' => 'Scrying Glass 2.0',
    ])->get(
        route('shares.condition-timers.player-summary.show', $share->token),
        [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ],
    )->assertOk();

    $response = $this->actingAs($manager)->get(
        route('groups.condition-timers.player-summary', $group),
        [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ],
    );

    $response->assertOk();

    $sharePayload = data_get($response->json(), 'props.share');

    expect(data_get($sharePayload, 'expiry.state'))->toBe('active');
    expect(data_get($sharePayload, 'stats.total_views'))->toBe(5);
    expect(data_get($sharePayload, 'stats.recent_accesses'))->toHaveCount(5);
    expect(data_get($sharePayload, 'stats.recent_accesses.0.ip_address'))->toBe('198.51.100.*');
    expect(data_get($sharePayload, 'stats.recent_accesses.0.user_agent'))->toBe('Scrying Glass 2.0');
    expect(data_get($sharePayload, 'stats.recent_accesses.1.ip_address'))->toBe('203.0.113.*');
    expect(data_get($sharePayload, 'stats.daily_views'))->toHaveCount(7);
    expect(collect(data_get($sharePayload, 'stats.daily_views'))->sum('total'))->toBe(4);
    expect(data_get($sharePayload, 'stats.daily_views.5.total'))->toBeGreaterThanOrEqual(1);

    CarbonImmutable::setTestNow();
});

it('lets facilitators configure and extend share expiry windows', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-11-05 15:00:00', 'UTC'));

    $manager = User::factory()->create();
    $group = Group::factory()->create([
        'created_by' => $manager->id,
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $this->actingAs($manager)
        ->post(route('groups.condition-timers.player-summary.share-links.store', $group), [
            'expires_in_hours' => 72,
        ])
        ->assertRedirect();

    $share = ConditionTimerSummaryShare::query()->where('group_id', $group->id)->firstOrFail();

    expect($share->expires_at?->equalTo(CarbonImmutable::now('UTC')->addHours(72)))->toBeTrue();

    $this->actingAs($manager)
        ->patch(route('groups.condition-timers.player-summary.share-links.update', [$group, $share]), [
            'expires_in_hours' => 120,
        ])
        ->assertRedirect();

    $share->refresh();

    expect($share->expires_at?->equalTo(CarbonImmutable::now('UTC')->addHours(120)))->toBeTrue();

    CarbonImmutable::setTestNow();
});
