<?php

use App\Events\AnalyticsEventDispatched;
use App\Models\AnalyticsEvent;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('records analytics events when telemetry is enabled', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    Event::fake();

    $response = $this->actingAs($user)->postJson(route('analytics.events.store'), [
        'key' => 'timer_summary.viewed',
        'group_id' => $group->id,
        'payload' => [
            'source' => 'session_panel',
            'entries_count' => 2,
            'staleness_ms' => 1200,
        ],
    ]);

    $response->assertOk();

    expect(AnalyticsEvent::query()->count())->toBe(1);

    $this->assertDatabaseHas('analytics_events', [
        'key' => 'timer_summary.viewed',
        'group_id' => $group->id,
    ]);

    Event::assertDispatched(AnalyticsEventDispatched::class, function (AnalyticsEventDispatched $event) use ($group): bool {
        return $event->key === 'timer_summary.viewed' && $event->groupId === $group->id;
    });
});

it('skips analytics when telemetry is disabled for the group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create([
        'telemetry_opt_out' => true,
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $response = $this->actingAs($user)->postJson(route('analytics.events.store'), [
        'key' => 'timer_summary.viewed',
        'group_id' => $group->id,
        'payload' => [
            'source' => 'session_panel',
            'entries_count' => 1,
        ],
    ]);

    $response->assertOk();

    expect(AnalyticsEvent::query()->count())->toBe(0);
});
