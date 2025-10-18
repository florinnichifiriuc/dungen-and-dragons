<?php

use App\Models\AiRequest;
use App\Models\ConditionTimerAcknowledgement;
use App\Models\ConditionTimerShareConsentLog;
use App\Models\ConditionTimerSummaryShare;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\User;
use App\Services\ConditionTimerSummaryProjector;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('simulates the transparency journey across share access, acknowledgements, and catch-up prompts', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2025-11-12T12:00:00Z'));

    /** @var User $manager */
    $manager = User::factory()->create();
    /** @var Group $group */
    $group = Group::factory()->create([
        'created_by' => $manager->id,
        'mentor_briefings_enabled' => true,
    ]);

    GroupMembership::query()->insert([
        [
            'group_id' => $group->id,
            'user_id' => $manager->id,
            'role' => GroupMembership::ROLE_OWNER,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    /** @var User $player */
    $player = User::factory()->create();

    GroupMembership::query()->insert([
        [
            'group_id' => $group->id,
            'user_id' => $player->id,
            'role' => GroupMembership::ROLE_PLAYER,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    ConditionTimerShareConsentLog::factory()->create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'recorded_by' => $manager->id,
        'action' => 'granted',
        'visibility' => 'details',
    ]);

    /** @var Map $map */
    $map = Map::factory()->for($group)->create();

    /** @var MapToken $token */
    $token = MapToken::factory()->for($map)->create([
        'status_conditions' => ['poisoned'],
        'status_condition_durations' => ['poisoned' => 4],
        'hidden' => false,
    ]);

    $summary = app(ConditionTimerSummaryProjector::class)->projectForGroup($group);
    $generatedAt = CarbonImmutable::parse($summary['generated_at']);

    $this->actingAs($manager)
        ->post(route('groups.condition-timers.player-summary.share-links.store', $group), [
            'visibility_mode' => 'details',
        ])
        ->assertRedirect();

    /** @var ConditionTimerSummaryShare $share */
    $share = ConditionTimerSummaryShare::query()->where('group_id', $group->id)->firstOrFail();

    config()->set('app.asset_url', 'http://localhost/build');
    $version = hash('xxh128', config('app.asset_url'));

    $firstView = $this->get(route('shares.condition-timers.player-summary.show', $share->token), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
    ]);

    $firstView->assertOk();

    $firstPayload = $firstView->json();

    expect(data_get($firstPayload, 'props.share.access_count'))->toBe(1);

    Carbon::setTestNow(CarbonImmutable::parse('2025-11-12T12:30:00Z'));

    $this->actingAs($player)
        ->postJson(route('groups.condition-timers.acknowledgements.store', $group), [
            'map_token_id' => $token->id,
            'condition_key' => 'poisoned',
            'summary_generated_at' => $generatedAt->toIso8601String(),
            'source' => 'offline',
            'queued_at' => CarbonImmutable::parse('2025-11-12T12:20:00Z')->toIso8601String(),
        ])
        ->assertOk()
        ->assertJsonPath('acknowledgement.acknowledged_by_viewer', true);

    expect(ConditionTimerAcknowledgement::query()->where('group_id', $group->id)->count())->toBe(1);

    AiRequest::factory()->create([
        'request_type' => 'mentor_briefing',
        'context_type' => Group::class,
        'context_id' => $group->id,
        'status' => AiRequest::STATUS_COMPLETED,
        'moderation_status' => AiRequest::MODERATION_APPROVED,
        'completed_at' => CarbonImmutable::parse('2025-11-12T13:15:00Z'),
        'response_text' => 'Focus on purging the poison lingering in Aelar’s veins.',
        'meta' => [
            'focus' => [
                'critical_conditions' => ['Aelar • Poisoned'],
                'unacknowledged_tokens' => [],
                'recurring_conditions' => [],
            ],
        ],
    ]);

    Carbon::setTestNow(CarbonImmutable::parse('2025-11-12T14:00:00Z'));

    $secondView = $this->get(route('shares.condition-timers.player-summary.show', $share->token), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
    ]);

    $secondView->assertOk();

    $secondPayload = $secondView->json();

    expect(data_get($secondPayload, 'props.catch_up_prompts'))->toBeArray()->not->toBeEmpty();
    expect(data_get($secondPayload, 'props.catch_up_prompts.0.excerpt'))->toContain('purging the poison');

    $share->refresh();
    expect($share->access_count)->toBe(2);
    expect($share->last_accessed_at?->toIso8601String())->toBe('2025-11-12T14:00:00+00:00');

    $share->forceFill([
        'expires_at' => CarbonImmutable::parse('2025-11-10T12:00:00Z'),
    ])->save();

    $thirdView = $this->get(route('shares.condition-timers.player-summary.show', $share->token), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
    ]);

    $thirdView->assertOk();

    $thirdPayload = $thirdView->json();

    expect(data_get($thirdPayload, 'props.summary.entries'))->toBeArray()->toBeEmpty();
    expect(data_get($thirdPayload, 'props.share.state.redacted'))->toBeTrue();

    $share->refresh();
    expect($share->access_count)->toBe(3);

    $accessEvents = $share->accesses()->where('event_type', 'access')->get();
    expect($accessEvents)->toHaveCount(3);
    expect($accessEvents->last()->metadata['quiet_hour_suppressed'] ?? null)->toBeFalse();

    Carbon::setTestNow();
});
