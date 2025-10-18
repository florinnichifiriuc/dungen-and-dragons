<?php

use App\Models\AiRequest;
use App\Models\BugReport;
use App\Models\Group;
use App\Models\User;
use App\Services\AnalyticsRecorder;
use App\Services\BugReportService;
use App\Services\BugWorkflowAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

afterEach(function (): void {
    \Mockery::close();
});

test('bug report creation composes description, environment, and ai context', function () {
    request()->headers->set('User-Agent', 'PlaywrightTest/1.0');
    request()->server->set('REMOTE_ADDR', '203.0.113.42');
    request()->server->set('REQUEST_URI', '/bug-reports/create');

    $group = Group::factory()->create();
    $submitter = User::factory()->create();

    AiRequest::factory()->create([
        'context_type' => Group::class,
        'context_id' => $group->id,
        'request_type' => 'mentor_briefing',
        'meta' => ['focus' => ['escalations']],
        'response_text' => 'Watch the groves closely.',
        'created_at' => now()->subMinutes(10),
    ]);

    AiRequest::factory()->create([
        'context_type' => Group::class,
        'context_id' => $group->id,
        'request_type' => 'summary',
        'created_by' => $submitter->id,
        'meta' => ['focus' => ['conditions']],
        'response_text' => 'Players triumphed over the hazard.',
        'created_at' => now()->subMinutes(2),
    ]);

    $analytics = \Mockery::mock(AnalyticsRecorder::class);
    $analytics->shouldReceive('record')
        ->once()
        ->with(
            'bug_report.created',
            \Mockery::on(fn ($payload) => Arr::get($payload, 'priority') === 'high' && Arr::get($payload, 'context_type') === 'facilitator'),
            \Mockery::on(fn ($actor) => $actor?->is($submitter) ?? false),
            \Mockery::on(fn ($groupParam) => $groupParam?->is($group) ?? false)
        );

    $automation = \Mockery::mock(BugWorkflowAutomationService::class);
    $automation->shouldReceive('notifyCreated')
        ->once()
        ->with(\Mockery::on(fn ($report) => $report instanceof BugReport));

    $service = new BugReportService($analytics, $automation);

    $report = $service->create([
        'summary' => 'E2E bug smoke scenario',
        'description' => 'Timer widget stalls.',
        'steps' => "1. Open dashboard\n2. Attempt to clear timer",
        'expected' => 'Timer clears immediately.',
        'actual' => 'Timer remains active.',
        'priority' => BugReport::PRIORITY_HIGH,
        'tags' => ['regression', 'playwright', 'regression'],
        'context_type' => 'facilitator',
        'context' => [
            'browser' => 'Firefox 120',
            'path' => '/groups/1/condition-timers',
            'locale' => 'en-GB',
            'logs' => ['TypeError: undefined is not a function'],
            'extra' => ['beta' => true],
        ],
        'ai_focus' => ['conditions'],
    ], $submitter, $group);

    $report->refresh();

    expect($report->priority)->toBe(BugReport::PRIORITY_HIGH);
    expect($report->description)
        ->toContain('Timer widget stalls.')
        ->toContain('Steps to reproduce:')
        ->toContain('Expected: Timer clears immediately.')
        ->toContain('Actual: Timer remains active.');

    expect($report->environment)
        ->toMatchArray([
            'user_agent' => 'PlaywrightTest/1.0',
            'ip' => '203.0.113.42',
            'browser' => 'Firefox 120',
            'path' => '/groups/1/condition-timers',
            'locale' => 'en-GB',
            'logs' => ['TypeError: undefined is not a function'],
            'extra' => ['beta' => true],
        ]);

    expect($report->tags)->toBe(['regression', 'playwright']);

    expect($report->ai_context)
        ->toHaveCount(2)
        ->and($report->ai_context[0])
        ->toHaveKey('focus_match', true);

    expect($report->updates()->count())->toBe(1);
    expect($report->updates()->first())->payload
        ->toMatchArray([
            'summary' => 'E2E bug smoke scenario',
            'priority' => BugReport::PRIORITY_HIGH,
            'context_type' => 'facilitator',
            'tags' => ['regression', 'playwright'],
        ]);
});

test('status transitions emit analytics and avoid duplicate history', function () {
    $actor = User::factory()->create();
    $report = BugReport::factory()->create([
        'status' => BugReport::STATUS_OPEN,
        'priority' => BugReport::PRIORITY_NORMAL,
    ]);

    $analytics = \Mockery::mock(AnalyticsRecorder::class);
    $analytics->shouldReceive('record')
        ->once()
        ->with(
            'bug_report.status_changed',
            \Mockery::on(fn ($payload) => Arr::get($payload, 'from') === BugReport::STATUS_OPEN && Arr::get($payload, 'to') === BugReport::STATUS_RESOLVED),
            \Mockery::on(fn ($actorArg) => $actorArg?->is($actor) ?? false),
            \Mockery::on(fn ($group) => $group?->is($report->group) ?? false)
        );

    $automation = \Mockery::mock(BugWorkflowAutomationService::class);
    $automation->shouldReceive('notifyStatusChange')
        ->once()
        ->with(
            \Mockery::on(fn ($model) => $model instanceof BugReport && $model->is($report)),
            BugReport::STATUS_OPEN,
            BugReport::STATUS_RESOLVED
        );

    $service = new BugReportService($analytics, $automation);

    $service->updateStatus($report, BugReport::STATUS_RESOLVED, $actor, 'Resolved after hotfix.');

    $report->refresh();

    expect($report->status)->toBe(BugReport::STATUS_RESOLVED);
    expect($report->updates()->where('type', 'status_changed')->count())->toBe(1);
    expect($report->updates()->where('type', 'status_changed')->first()->payload)
        ->toMatchArray([
            'from' => BugReport::STATUS_OPEN,
            'to' => BugReport::STATUS_RESOLVED,
            'note' => 'Resolved after hotfix.',
        ]);

    // Duplicate status should be ignored and avoid additional automation or analytics calls.
    $service->updateStatus($report->fresh(), BugReport::STATUS_RESOLVED, $actor);

    expect($report->refresh()->updates()->where('type', 'status_changed')->count())->toBe(1);
});

test('priority, tags, and assignments are tracked with analytics', function () {
    $actor = User::factory()->create();
    $assignee = User::factory()->supportAdmin()->create();
    $report = BugReport::factory()->create([
        'tags' => ['legacy'],
        'priority' => BugReport::PRIORITY_LOW,
    ]);

    $analytics = \Mockery::mock(AnalyticsRecorder::class);
    $analytics->shouldReceive('record')
        ->once()
        ->with(
            'bug_report.updated',
            \Mockery::on(fn ($payload) => Arr::get($payload, 'priority') === BugReport::PRIORITY_CRITICAL && Arr::get($payload, 'tags') === ['triage', 'playwright']),
            \Mockery::on(fn ($actorArg) => $actorArg?->is($actor) ?? false),
            \Mockery::on(fn ($group) => $group?->is($report->group) ?? false)
        )
        ->ordered();

    $analytics->shouldReceive('record')
        ->once()
        ->with(
            'bug_report.assigned',
            \Mockery::on(fn ($payload) => Arr::get($payload, 'assignee') === $assignee->id),
            \Mockery::on(fn ($actorArg) => $actorArg?->is($actor) ?? false),
            \Mockery::on(fn ($group) => $group?->is($report->group) ?? false)
        )
        ->ordered();

    $automation = \Mockery::mock(BugWorkflowAutomationService::class);
    $automation->shouldReceive('notifyAssignment')
        ->once()
        ->with(
            \Mockery::on(fn ($model) => $model instanceof BugReport && $model->is($report)),
            \Mockery::on(fn ($user) => $user?->is($assignee) ?? false)
        );

    $service = new BugReportService($analytics, $automation);

    $service->updateDetails($report, [
        'priority' => BugReport::PRIORITY_CRITICAL,
        'tags' => ['triage', 'playwright', 'triage'],
    ], $actor);

    $service->assign($report->fresh(), $assignee, $actor);

    $report->refresh();

    expect($report->priority)->toBe(BugReport::PRIORITY_CRITICAL);
    expect($report->tags)->toBe(['triage', 'playwright']);
    expect($report->assignee?->is($assignee))->toBeTrue();

    expect($report->updates()->where('type', 'attributes_updated')->count())->toBe(1);
    expect($report->updates()->where('type', 'assignment')->count())->toBe(1);
});

test('comments record analytics and capture payload', function () {
    $actor = User::factory()->create();
    $report = BugReport::factory()->create();

    $analytics = \Mockery::mock(AnalyticsRecorder::class);
    $analytics->shouldReceive('record')
        ->once()
        ->with(
            'bug_report.commented',
            \Mockery::on(fn ($payload) => Arr::get($payload, 'report') === $report->id),
            \Mockery::on(fn ($actorArg) => $actorArg?->is($actor) ?? false),
            \Mockery::on(fn ($group) => $group?->is($report->group) ?? false)
        );

    $automation = \Mockery::mock(BugWorkflowAutomationService::class);
    $automation->shouldReceive('notifyCreated')->never();
    $automation->shouldReceive('notifyStatusChange')->never();
    $automation->shouldReceive('notifyAssignment')->never();

    $service = new BugReportService($analytics, $automation);

    $update = $service->addComment($report, 'Investigating reproduction steps.', $actor);

    expect($update->type)->toBe('comment');
    expect($update->payload)->toMatchArray(['body' => 'Investigating reproduction steps.']);
});
