<?php

namespace App\Jobs;

use App\Models\BugReport;
use App\Notifications\BugReportDigestNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SendBugReportDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle(): void
    {
        $reports = BugReport::query()
            ->whereIn('status', [
                BugReport::STATUS_OPEN,
                BugReport::STATUS_IN_PROGRESS,
            ])
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->take(25)
            ->get();

        $notifiables = $this->resolveNotifiables();

        if ($notifiables === []) {
            Log::info('bug_report_digest_skipped', ['reason' => 'no-watchers']);

            return;
        }

        $notification = new BugReportDigestNotification($reports);

        foreach ($notifiables as $notifiable) {
            $notifiable->notify($notification);
        }
    }

    protected function resolveNotifiables(): array
    {
        $watchers = [];
        $configWatchers = Config::get('bug-reporting.watchers', []);

        foreach ($configWatchers as $email) {
            $watchers[] = Notification::route('mail', $email);
        }

        $users = \App\Models\User::query()
            ->where('is_support_admin', true)
            ->get();

        foreach ($users as $user) {
            $watchers[] = $user;
        }

        if (in_array('slack', Config::get('bug-reporting.digest_channels', []), true)) {
            $webhooks = Config::get('bug-reporting.slack_webhooks', []);

            foreach ($webhooks as $webhook) {
                if (is_string($webhook) && $webhook !== '') {
                    $watchers[] = Notification::route('slack', $webhook);
                }
            }
        }

        return $watchers;
    }
}
