<?php

use App\Models\Campaign;
use App\Models\ConditionTimerAcknowledgement;
use App\Models\ConditionTimerAdjustment;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\User;
use App\Services\ConditionTimerSummaryProjector;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

it('allows campaign managers to view facilitator insights', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2025-11-07 12:00:00', 'UTC'));

    $manager = User::factory()->create();
    $group = Group::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_DUNGEON_MASTER,
    ]);

    $campaign = Campaign::factory()
        ->for($group)
        ->create([
            'created_by' => $manager->id,
        ]);

    $map = Map::factory()->for($group)->create();
    $token = MapToken::factory()->for($map)->create([
        'status_conditions' => ['poisoned', 'restrained'],
        'status_condition_durations' => ['poisoned' => 2, 'restrained' => 4],
        'hidden' => false,
        'faction' => MapToken::FACTION_ALLIED,
    ]);

    ConditionTimerAcknowledgement::factory()->create([
        'group_id' => $group->id,
        'map_token_id' => $token->id,
        'user_id' => $manager->id,
        'condition_key' => 'poisoned',
        'summary_generated_at' => CarbonImmutable::now('UTC'),
        'acknowledged_at' => CarbonImmutable::now('UTC')->addMinutes(12),
    ]);

    foreach (range(1, 3) as $offset) {
        ConditionTimerAdjustment::factory()->create([
            'group_id' => $group->id,
            'map_token_id' => $token->id,
            'condition_key' => 'restrained',
            'previous_rounds' => 5,
            'new_rounds' => 3,
            'delta' => -2,
            'recorded_at' => CarbonImmutable::now('UTC')->subHours($offset),
        ]);
    }

    /** @var ConditionTimerSummaryProjector $projector */
    $projector = app(ConditionTimerSummaryProjector::class);
    $projector->forgetForGroup($group);

    Config::set('inertia.testing.ensure_manifest', false);
    $this->withoutVite();

    $response = $this->actingAs($manager)->get(route('campaigns.insights.show', $campaign));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Campaigns/Insights')
        ->where('campaign.id', $campaign->id)
        ->where('insights.metrics.total_active', fn ($count) => $count >= 1)
        ->where('insights.repeat_offenders.0.adjustments_count', 3)
        ->where('insights.at_risk_players.0.conditions.0.key', 'restrained')
    );

    Carbon::setTestNow();
});

it('prevents players from accessing facilitator insights', function () {
    $manager = User::factory()->create();
    $player = User::factory()->create();
    $group = Group::factory()->create();

    GroupMembership::query()->insert([
        [
            'group_id' => $group->id,
            'user_id' => $manager->id,
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

    $campaign = Campaign::factory()
        ->for($group)
        ->create([
            'created_by' => $manager->id,
        ]);

    $response = $this->actingAs($player)->get(route('campaigns.insights.show', $campaign));

    $response->assertForbidden();
});
