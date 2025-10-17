<?php

use App\Events\ConditionTimerAcknowledgementRecorded;
use App\Models\AnalyticsEvent;
use App\Models\ConditionTimerAcknowledgement;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\User;
use App\Services\ConditionTimerAcknowledgementService;
use App\Services\ConditionTimerSummaryProjector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('records acknowledgements and broadcasts updates', function () {
    Event::fake([ConditionTimerAcknowledgementRecorded::class]);

    $user = User::factory()->create();
    $group = Group::factory()->create();
    GroupMembership::query()->create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $map = Map::factory()->for($group)->create();
    $token = MapToken::factory()->for($map)->create([
        'status_conditions' => ['blinded'],
        'status_condition_durations' => ['blinded' => 3],
    ]);

    $summaryTimestamp = Carbon::now('UTC')->toIso8601String();

    Carbon::setTestNow(Carbon::parse($summaryTimestamp));

    AnalyticsEvent::query()->delete();

    $response = $this
        ->actingAs($user)
        ->postJson(route('groups.condition-timers.acknowledgements.store', $group), [
            'map_token_id' => $token->id,
            'condition_key' => 'blinded',
            'summary_generated_at' => $summaryTimestamp,
        ]);

    $response->assertOk();
    $response->assertJsonPath('acknowledgement.acknowledged_by_viewer', true);
    $response->assertJsonPath('acknowledgement.acknowledged_count', 1);
    $response->assertJsonPath('acknowledgement.source', 'online');
    $response->assertJsonPath('acknowledgement.queued_at', null);

    $this->assertDatabaseHas('condition_timer_acknowledgements', [
        'group_id' => $group->id,
        'map_token_id' => $token->id,
        'user_id' => $user->id,
        'condition_key' => 'blinded',
        'source' => 'online',
    ]);

    $acknowledgement = ConditionTimerAcknowledgement::query()->where('group_id', $group->id)->firstOrFail();

    expect($acknowledgement->queued_at)->toBeNull();
    expect($acknowledgement->acknowledged_at?->toIso8601String())->toBe($response->json('acknowledgement.acknowledged_at'));

    Event::assertDispatched(ConditionTimerAcknowledgementRecorded::class, function (ConditionTimerAcknowledgementRecorded $event) use ($group, $token) {
        return $event->groupId === $group->id
            && $event->tokenId === $token->id
            && $event->conditionKey === 'blinded'
            && $event->acknowledgedCount === 1;
    });

    expect(AnalyticsEvent::query()->where('key', 'timer_summary.acknowledged')->count())->toBeGreaterThanOrEqual(1);

    Carbon::setTestNow();
});

it('stores queued timestamp when acknowledgement is synced after reconnecting', function () {
    Event::fake([ConditionTimerAcknowledgementRecorded::class]);

    $user = User::factory()->create();
    $group = Group::factory()->create();
    GroupMembership::query()->create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $map = Map::factory()->for($group)->create();
    $token = MapToken::factory()->for($map)->create([
        'status_conditions' => ['frightened'],
        'status_condition_durations' => ['frightened' => 2],
    ]);

    $summaryGeneratedAt = CarbonImmutable::parse('2025-11-10T09:45:00+00:00');
    $queuedAt = CarbonImmutable::parse('2025-11-10T10:00:00+00:00');

    Carbon::setTestNow(Carbon::parse('2025-11-10T10:05:00+00:00'));

    AnalyticsEvent::query()->delete();

    $response = $this
        ->actingAs($user)
        ->postJson(route('groups.condition-timers.acknowledgements.store', $group), [
            'map_token_id' => $token->id,
            'condition_key' => 'frightened',
            'summary_generated_at' => $summaryGeneratedAt->toIso8601String(),
            'source' => 'offline',
            'queued_at' => $queuedAt->toIso8601String(),
        ]);

    $response->assertOk();
    $response->assertJsonPath('acknowledgement.source', 'offline');
    $response->assertJsonPath('acknowledgement.queued_at', $queuedAt->toIso8601String());
    $response->assertJsonPath('acknowledgement.acknowledged_at', $queuedAt->toIso8601String());

    $acknowledgement = ConditionTimerAcknowledgement::query()->where('group_id', $group->id)->where('map_token_id', $token->id)->firstOrFail();

    expect($acknowledgement->source)->toBe('offline');
    expect($acknowledgement->queued_at?->toIso8601String())->toBe($queuedAt->toIso8601String());
    expect($acknowledgement->acknowledged_at?->toIso8601String())->toBe($queuedAt->toIso8601String());

    $event = AnalyticsEvent::query()->where('key', 'timer_summary.acknowledged')->first();

    expect($event)->not->toBeNull();
    expect($event?->payload['source'] ?? null)->toBe('offline');
    expect($event?->payload['queued_at'] ?? null)->toBe($queuedAt->toIso8601String());
    expect($event?->payload['acknowledged_at'] ?? null)->toBe($queuedAt->toIso8601String());
    expect($event?->payload['sync_lag_ms'] ?? null)->toBe(300000);

    Carbon::setTestNow();
});

it('hydrates summaries with acknowledgement metadata', function () {
    $acknowledgements = app(ConditionTimerAcknowledgementService::class);
    $projector = app(ConditionTimerSummaryProjector::class);

    $group = Group::factory()->create();
    $player = User::factory()->create();
    $dm = User::factory()->create();

    GroupMembership::query()->insert([
        [
            'group_id' => $group->id,
            'user_id' => $player->id,
            'role' => GroupMembership::ROLE_PLAYER,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'group_id' => $group->id,
            'user_id' => $dm->id,
            'role' => GroupMembership::ROLE_DUNGEON_MASTER,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $map = Map::factory()->for($group)->create();
    $token = MapToken::factory()->for($map)->create([
        'status_conditions' => ['blinded'],
        'status_condition_durations' => ['blinded' => 2],
    ]);

    $summary = $projector->projectForGroup($group);

    $generatedAt = CarbonImmutable::parse($summary['generated_at']);

    $acknowledgements->recordAcknowledgement($group, $token, 'blinded', $generatedAt, $player);

    $playerSummary = $acknowledgements->hydrateSummaryForUser($summary, $group, $player, false);
    $dmSummary = $acknowledgements->hydrateSummaryForUser($summary, $group, $dm, true);

    $playerCondition = $playerSummary['entries'][0]['conditions'][0];
    $dmCondition = $dmSummary['entries'][0]['conditions'][0];

    expect($playerCondition['acknowledged_by_viewer'])->toBeTrue();
    expect($playerCondition)->not->toHaveKey('acknowledged_count');

    expect($dmCondition['acknowledged_by_viewer'])->toBeFalse();
    expect($dmCondition['acknowledged_count'])->toBe(1);
});
