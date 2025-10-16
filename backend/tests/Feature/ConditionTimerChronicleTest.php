<?php

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\User;
use App\Services\ConditionTimerChronicleService;
use App\Services\ConditionTimerSummaryProjector;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records adjustments and hydrates timelines by role', function () {
    $dm = User::factory()->create();
    $player = User::factory()->create();
    $group = Group::factory()->create();

    GroupMembership::query()->insert([
        [
            'group_id' => $group->id,
            'user_id' => $dm->id,
            'role' => GroupMembership::ROLE_DUNGEON_MASTER,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'group_id' => $group->id,
            'user_id' => $player->id,
            'role' => GroupMembership::ROLE_PLAYER,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $map = Map::factory()->for($group)->create();
    $token = MapToken::factory()->for($map)->create([
        'status_conditions' => ['blinded'],
        'status_condition_durations' => ['blinded' => 3],
        'hidden' => false,
        'faction' => MapToken::FACTION_ALLIED,
    ]);

    $response = $this
        ->actingAs($dm)
        ->post(route('groups.maps.tokens.condition-timers.batch', [$group, $map]), [
            'adjustments' => [
                [
                    'token_id' => $token->id,
                    'condition' => 'blinded',
                    'delta' => 2,
                ],
            ],
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('condition_timer_adjustments', [
        'group_id' => $group->id,
        'map_token_id' => $token->id,
        'condition_key' => 'blinded',
        'reason' => 'manual_adjustment',
    ]);

    /** @var ConditionTimerSummaryProjector $projector */
    $projector = app(ConditionTimerSummaryProjector::class);
    /** @var ConditionTimerChronicleService $chronicle */
    $chronicle = app(ConditionTimerChronicleService::class);

    $projector->refreshForGroup($group, 'test', false);
    $summary = $projector->projectForGroup($group);

    $playerSummary = $chronicle->hydrateSummaryForUser($summary, $group, $player, false);
    $dmSummary = $chronicle->hydrateSummaryForUser($summary, $group, $dm, true);

    $playerTimeline = $playerSummary['entries'][0]['conditions'][0]['timeline'] ?? [];
    expect($playerTimeline)->not->toBeEmpty();
    expect($playerTimeline[0])->not->toHaveKey('detail');
    expect($playerTimeline[0]['summary'])->toContain('Timer extended');

    $dmTimeline = $dmSummary['entries'][0]['conditions'][0]['timeline'] ?? [];
    expect($dmTimeline)->not->toBeEmpty();
    expect($dmTimeline[0]['detail']['actor']['id'])->toBe($dm->id);
    expect($dmTimeline[0]['detail']['previous_rounds'])->toBe(3);
    expect($dmTimeline[0]['detail']['new_rounds'])->toBe(5);
    expect($dmTimeline[0]['detail']['summary'])->toContain('Manual adjustment');
});

it('exposes chronicle entries in exports based on permissions', function () {
    $dm = User::factory()->create();
    $player = User::factory()->create();
    $group = Group::factory()->create();

    GroupMembership::query()->insert([
        [
            'group_id' => $group->id,
            'user_id' => $dm->id,
            'role' => GroupMembership::ROLE_DUNGEON_MASTER,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'group_id' => $group->id,
            'user_id' => $player->id,
            'role' => GroupMembership::ROLE_PLAYER,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $map = Map::factory()->for($group)->create();
    $token = MapToken::factory()->for($map)->create([
        'status_conditions' => ['poisoned'],
        'status_condition_durations' => ['poisoned' => 4],
        'hidden' => false,
        'faction' => MapToken::FACTION_ALLIED,
    ]);

    /** @var ConditionTimerChronicleService $chronicle */
    $chronicle = app(ConditionTimerChronicleService::class);
    $chronicle->recordAdjustments(
        $group,
        $token,
        [
            [
                'condition' => 'poisoned',
                'previous' => 4,
                'next' => 2,
            ],
        ],
        'manual_adjustment',
        $dm,
        ['source' => 'export_test'],
    );

    $campaign = \App\Models\Campaign::factory()->for($group)->create(['created_by' => $dm->id]);
    $session = \App\Models\CampaignSession::factory()
        ->for($campaign)
        ->create([
            'created_by' => $dm->id,
            'summary' => 'Test session summary.',
        ]);

    /** @var \App\Services\SessionExportService $exports */
    $exports = app(\App\Services\SessionExportService::class);

    $dmExport = $exports->buildExportData($session->fresh(), $dm);
    $playerExport = $exports->buildExportData($session->fresh(), $player);

    expect($dmExport['condition_timer_chronicle'])->not->toBeEmpty();
    expect($dmExport['condition_timer_chronicle'][0]['previous_rounds'])->toBe(4);
    expect($dmExport['condition_timer_chronicle'][0]['actor']['id'])->toBe($dm->id);

    expect($playerExport['condition_timer_chronicle'])->not->toBeEmpty();
    expect($playerExport['condition_timer_chronicle'][0]['previous_rounds'])->toBeNull();
    expect($playerExport['condition_timer_chronicle'][0]['actor'])->toBeNull();
});
