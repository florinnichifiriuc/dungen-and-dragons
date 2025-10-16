<?php

use App\Events\ConditionTimerAcknowledgementRecorded;
use App\Models\AnalyticsEvent;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\User;
use App\Services\ConditionTimerAcknowledgementService;
use App\Services\ConditionTimerSummaryProjector;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

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

    $this->assertDatabaseHas('condition_timer_acknowledgements', [
        'group_id' => $group->id,
        'map_token_id' => $token->id,
        'user_id' => $user->id,
        'condition_key' => 'blinded',
    ]);

    Event::assertDispatched(ConditionTimerAcknowledgementRecorded::class, function (ConditionTimerAcknowledgementRecorded $event) use ($group, $token) {
        return $event->groupId === $group->id
            && $event->tokenId === $token->id
            && $event->conditionKey === 'blinded'
            && $event->acknowledgedCount === 1;
    });

    expect(AnalyticsEvent::query()->where('key', 'timer_summary.acknowledged')->count())->toBe(1);

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
