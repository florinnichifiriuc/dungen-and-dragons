<?php

namespace App\Services;

use App\Jobs\SendBugReportDigestJob;
use App\Jobs\TriggerPagerDutyIncidentJob;
use App\Models\BugReport;
use App\Models\User;
use App\Notifications\BugReportEscalatedNotification;
use App\Notifications\BugReportSlackNotification;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class BugWorkflowAutomationService
{
    public function notifyCreated(BugReport $report): void
    {
        Log::info('bug_report_created', [
            'reference' => $report->reference,
            'priority' => $report->priority,
            'status' => $report->status,
        ]);

        if (in_array($report->priority, [BugReport::PRIORITY_HIGH, BugReport::PRIORITY_CRITICAL], true)) {
            $this->dispatchEscalation($report);
            $this->dispatchSlackAlert($report);
            $this->triggerPagerDuty($report);
        }

        Bus::dispatch(new SendBugReportDigestJob());
    }

    public function notifyStatusChange(BugReport $report, string $previous, string $next): void
    {
        Log::info('bug_report_status_changed', [
            'reference' => $report->reference,
            'from' => $previous,
            'to' => $next,
        ]);

        if ($next === BugReport::STATUS_RESOLVED || $next === BugReport::STATUS_CLOSED) {
            Bus::dispatch(new SendBugReportDigestJob());
        }
    }

    public function notifyAssignment(BugReport $report, ?User $assignee): void
    {
        if (! $assignee) {
            return;
        }

        $assignee->notify(new BugReportEscalatedNotification($report));
    }

    protected function dispatchEscalation(BugReport $report): void
    {
        $notification = new BugReportEscalatedNotification($report);

        foreach ($this->resolveWatchers() as $notifiable) {
            $notifiable->notify($notification);
        }
    }

    protected function dispatchSlackAlert(BugReport $report): void
    {
        $notification = new BugReportSlackNotification($report);

        foreach ($this->resolveSlackNotifiables() as $notifiable) {
            $notifiable->notify($notification);
        }
    }

    protected function triggerPagerDuty(BugReport $report): void
    {
        $routingKey = (string) Config::get('bug-reporting.pagerduty.routing_key');

        if ($routingKey === '') {
            Log::info('bug_report_pagerduty_skipped', ['reason' => 'missing-routing-key']);

            return;
        }

        $delay = $this->pagerDutyDelay();

        $job = new TriggerPagerDutyIncidentJob($report->getKey());

        if ($delay !== null) {
            $job->delay($delay);
        }

        Bus::dispatch($job);
    }

    protected function pagerDutyDelay(): ?CarbonInterval
    {
        $quietHours = Config::get('bug-reporting.quiet_hours');

        if (! is_array($quietHours)) {
            return null;
        }

        $timezone = $quietHours['timezone'] ?? 'UTC';
        $start = $quietHours['start'] ?? null;
        $end = $quietHours['end'] ?? null;

        if (! $start || ! $end || $start === $end) {
            return null;
        }

        $now = CarbonImmutable::now($timezone);

        $startTime = CarbonImmutable::parse($start, $timezone)->setDate($now->year, $now->month, $now->day);
        $endTime = CarbonImmutable::parse($end, $timezone)->setDate($now->year, $now->month, $now->day);

        if ($startTime->greaterThan($endTime)) {
            if ($now->greaterThanOrEqualTo($startTime)) {
                $endTime = $endTime->addDay();
            } else {
                $startTime = $startTime->subDay();
            }
        }

        $inQuietWindow = $now->betweenIncluded($startTime, $endTime);

        if (! $inQuietWindow) {
            return null;
        }

        return $now->diffAsCarbonInterval($endTime)->ceilMinutes();
    }

    /**
     * @return array<int, \Illuminate\Notifications\AnonymousNotifiable|User>
     */
    protected function resolveWatchers(): array
    {
        $watchers = [];

        $configWatchers = Config::get('bug-reporting.watchers', []);

        foreach ($configWatchers as $email) {
            $watchers[] = Notification::route('mail', $email);
        }

        $users = User::query()
            ->where('is_support_admin', true)
            ->get();

        foreach ($users as $user) {
            $watchers[] = $user;
        }

        return $watchers;
    }

    /**
     * @return array<int, \Illuminate\Notifications\AnonymousNotifiable>
     */
    protected function resolveSlackNotifiables(): array
    {
        $webhooks = Config::get('bug-reporting.slack_webhooks', []);

        if (! is_array($webhooks) || $webhooks === []) {
            return [];
        }

        return array_map(
            static fn (string $webhook) => Notification::route('slack', $webhook),
            array_filter($webhooks, static fn ($value) => is_string($value) && $value !== '')
        );
    }
}
