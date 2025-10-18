<?php

namespace App\Notifications;

use App\Models\BugReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class BugReportSlackNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly BugReport $report)
    {
    }

    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $report = $this->report;

        return (new SlackMessage())
            ->from('Launch Bug Sentinel')
            ->warning()
            ->content(sprintf('Critical bug %s reported: %s', $report->reference, $report->summary))
            ->attachment(function ($attachment) use ($report): void {
                $attachment
                    ->title('Review in triage dashboard', route('admin.bug-reports.show', $report))
                    ->fields([
                        'Priority' => ucfirst($report->priority),
                        'Status' => ucfirst(str_replace('_', ' ', $report->status)),
                        'Context' => ucfirst($report->context_type),
                    ]);
            });
    }
}
