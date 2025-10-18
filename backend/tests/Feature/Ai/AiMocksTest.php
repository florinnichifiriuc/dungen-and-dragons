<?php

use App\Models\Group;
use App\Models\User;
use App\Services\AiContentService;
use App\Services\AiContentFake;
use App\Support\Ai\AiResponseFixtureRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ai content service resolves to the mock when mocks are enabled', function () {
    $service = app(AiContentService::class);

    expect($service)->toBeInstanceOf(AiContentFake::class);
});

test('ai mock fixtures return deterministic mentor briefings', function () {
    $group = Group::factory()->create();

    /** @var AiContentService $service */
    $service = app(AiContentService::class);

    $result = $service->mentorBriefing($group, ['focus' => ['critical_conditions' => []]]);

    expect($result['briefing'])
        ->toContain('Mentor')
        ->toContain('grove');

    $request = $result['request'];

    expect($request->response_payload)
        ->toBeArray()
        ->toHaveKey('mocked', true);
});

test('ai fixtures can be overridden during tests', function () {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    /** @var AiResponseFixtureRepository $repository */
    $repository = app(AiResponseFixtureRepository::class);

    $repository->put('mentor_briefing', [
        'response' => 'Override briefing for testing expectations.',
        'payload' => [
            'fixture' => 'override',
        ],
    ]);

    /** @var AiContentService $service */
    $service = app(AiContentService::class);

    $result = $service->mentorBriefing($group, ['focus' => ['critical_conditions' => []]], $user);

    expect($result['briefing'])->toBe('Override briefing for testing expectations.');
    expect($result['request']->response_payload['fixture'])->toBe('override');

    $repository->clear('mentor_briefing');
});
