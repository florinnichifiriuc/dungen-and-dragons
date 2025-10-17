<?php

use App\Models\Group;
use App\Services\AiContentService;
use App\Services\ConditionMentorBriefingService;
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

    $ai->shouldReceive('mentorBriefing')->once()->with($group, \Mockery::type('array'))->andReturn([
        'briefing' => 'Focus on detoxifying Sir Reginald.',
    ]);

    $service = new ConditionMentorBriefingService($projector, $chronicle, $ai);

    $first = $service->getBriefing($group);
    $second = $service->getBriefing($group);

    expect($first)->toHaveKey('briefing', 'Focus on detoxifying Sir Reginald.');
    expect($first['focus']['critical_conditions'])->toContain('Sir Reginald • Poisoned');
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
    );

    $service->setEnabled($group, false);

    $group->refresh();

    expect($group->mentor_briefings_enabled)->toBeFalse();
    expect(Cache::has(sprintf('group:%d:mentor-briefing', $group->id)))->toBeFalse();
});
