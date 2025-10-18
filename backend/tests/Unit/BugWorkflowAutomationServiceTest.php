<?php

use App\Jobs\SendBugReportDigestJob;
use App\Jobs\TriggerPagerDutyIncidentJob;
use App\Models\BugReport;
use App\Models\User;
use App\Notifications\BugReportEscalatedNotification;
use App\Notifications\BugReportSlackNotification;
use App\Services\BugWorkflowAutomationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
    Bus::fake();

    Config::set('bug-reporting.watchers', ['ops@example.com']);
    Config::set('bug-reporting.slack_webhooks', ['https://hooks.slack.com/services/test']);
    Config::set('bug-reporting.pagerduty.routing_key', 'routing-key');
    Config::set('bug-reporting.digest_channels', ['mail', 'slack']);
    Config::set('bug-reporting.quiet_hours', [
        'start' => '02:00',
        'end' => '07:00',
        'timezone' => 'UTC',
    ]);
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

test('high priority bug dispatches automation alerts immediately', function () {
    $admin = User::factory()->supportAdmin()->create();
    $report = BugReport::factory()->create([
        'priority' => BugReport::PRIORITY_HIGH,
        'status' => BugReport::STATUS_OPEN,
    ]);

    $service = app(BugWorkflowAutomationService::class);

    $service->notifyCreated($report);

    Notification::assertSentTo($admin, BugReportEscalatedNotification::class);

    Notification::assertSentOnDemand(BugReportEscalatedNotification::class, function ($notification, array $channels, $notifiable) use ($report) {
        return in_array('mail', $channels, true)
            && ($notifiable->routes['mail'] ?? null) === 'ops@example.com'
            && $notification->report->is($report);
    });

    Notification::assertSentOnDemand(BugReportSlackNotification::class, function ($notification, array $channels, $notifiable) use ($report) {
        return in_array('slack', $channels, true)
            && ($notifiable->routes['slack'] ?? null) === 'https://hooks.slack.com/services/test'
            && $notification->report->is($report);
    });

    Bus::assertDispatched(SendBugReportDigestJob::class);

    Bus::assertDispatched(TriggerPagerDutyIncidentJob::class, function (TriggerPagerDutyIncidentJob $job) use ($report) {
        return $job->bugReportId === $report->id && $job->delay === null;
    });
});

test('pagerduty alerts are delayed during quiet hours', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-11-23 03:00', 'UTC'));

    $report = BugReport::factory()->create([
        'priority' => BugReport::PRIORITY_CRITICAL,
        'status' => BugReport::STATUS_OPEN,
    ]);

    $service = app(BugWorkflowAutomationService::class);

    $service->notifyCreated($report);

    Bus::assertDispatched(TriggerPagerDutyIncidentJob::class, function (TriggerPagerDutyIncidentJob $job) {
        return $job->delay !== null;
    });
});
