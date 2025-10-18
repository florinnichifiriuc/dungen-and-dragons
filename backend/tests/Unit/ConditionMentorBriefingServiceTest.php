<?php

use App\Models\AiRequest;
use App\Models\Group;
use App\Services\AiContentService;
use App\Services\ConditionMentorBriefingService;
use App\Services\ConditionMentorModerationService;
use App\Services\ConditionTimerChronicleService;
use App\Services\ConditionTimerSummaryProjector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

uses()->beforeEach(function (): void {
    Cache::clear();
});

afterEach(function (): void {
    \Mockery::close();
});

it('returns an empty response when mentor briefings are disabled', function () {
    $group = Group::factory()->create([
        'mentor_briefings_enabled' => false,
    ]);

    $service = new ConditionMentorBriefingService(
        \Mockery::mock(ConditionTimerSummaryProjector::class),
        \Mockery::mock(ConditionTimerChronicleService::class),
        \Mockery::mock(AiContentService::class),
        new ConditionMentorModerationService(),
    );

    expect($service->getBriefing($group))->toBeArray()->toBeEmpty();
});

it('generates and caches mentor briefings when enabled', function () {
    $group = Group::factory()->create([
        'mentor_briefings_enabled' => true,
    ]);

    $projector = \Mockery::mock(ConditionTimerSummaryProjector::class);
    $chronicle = \Mockery::mock(ConditionTimerChronicleService::class);
    $ai = \Mockery::mock(AiContentService::class);
    $moderation = new ConditionMentorModerationService();

    $projector->shouldReceive('projectForGroup')->once()->with($group)->andReturn([
        'entries' => [
            [
                'token' => ['label' => 'Sir Reginald'],
                'conditions' => [
                    [
                        'label' => 'Poisoned',
                        'urgency' => 'critical',
                        'acknowledged_count' => 0,
                        'exposes_exact_rounds' => true,
                    ],
                ],
            ],
        ],
    ]);

    $chronicle->shouldReceive('exportChronicle')->once()->with($group, false, 50)->andReturn([]);

    $request = AiRequest::factory()->make([
        'request_type' => 'mentor_briefing',
        'context_type' => Group::class,
        'context_id' => $group->id,
        'status' => AiRequest::STATUS_COMPLETED,
        'response_text' => 'Focus on detoxifying Sir Reginald.',
        'meta' => [
            'focus' => [
                'critical_conditions' => ['Sir Reginald • Poisoned'],
                'unacknowledged_tokens' => [],
                'recurring_conditions' => [],
            ],
        ],
        'moderation_status' => AiRequest::MODERATION_PENDING,
    ]);

    $ai->shouldReceive('mentorBriefing')->once()->with($group, \Mockery::type('array'))->andReturn([
        'request' => $request,
        'briefing' => 'Focus on detoxifying Sir Reginald.',
    ]);

    $service = new ConditionMentorBriefingService($projector, $chronicle, $ai, $moderation);

    $first = $service->getBriefing($group);
    $second = $service->getBriefing($group);

    expect($first)->toHaveKey('briefing', 'Focus on detoxifying Sir Reginald.');
    expect($first['focus']['critical_conditions'])->toContain('Sir Reginald • Poisoned');
    expect($first['moderation']['status'])->toBe(AiRequest::MODERATION_APPROVED);
    expect($first['pending_queue'])->toBeArray()->toBeEmpty();
    expect($first['playback_digest'])->toBeArray()->toBeEmpty();
    expect($second)->toBe($first);

    $group->refresh();
    expect($group->mentor_briefings_last_generated_at)->not->toBeNull();
});

it('toggles mentor briefings and clears cached payloads when disabled', function () {
    $group = Group::factory()->create([
        'mentor_briefings_enabled' => true,
    ]);

    Cache::put(sprintf('group:%d:mentor-briefing', $group->id), ['briefing' => 'cached'], now()->addMinutes(30));

    $service = new ConditionMentorBriefingService(
        \Mockery::mock(ConditionTimerSummaryProjector::class),
        \Mockery::mock(ConditionTimerChronicleService::class),
        \Mockery::mock(AiContentService::class),
        new ConditionMentorModerationService(),
    );

    $service->setEnabled($group, false);

    $group->refresh();

    expect($group->mentor_briefings_enabled)->toBeFalse();
    expect(Cache::has(sprintf('group:%d:mentor-briefing', $group->id)))->toBeFalse();
});
