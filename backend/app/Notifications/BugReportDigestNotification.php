<?php

namespace App\Notifications;

use App\Models\BugReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Notifications\AnonymousNotifiable;

class BugReportDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, BugReport>  $reports
     */
    public function __construct(protected Collection $reports)
    {
    }

    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable && $notifiable->routeNotificationFor('slack')) {
            return ['slack'];
        }

        $channels = ['mail'];

        if ($notifiable instanceof \App\Models\User) {
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject('Daily Bug Report Digest')
            ->line('Here is the latest summary of active bug reports.');

        foreach ($this->reports as $report) {
            $message->line(sprintf(
                '%s • %s • %s',
                $report->reference,
                ucfirst($report->priority),
                $report->summary
            ));
        }

        $message->action('Open bug triage dashboard', route('admin.bug-reports.index'));

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'count' => $this->reports->count(),
        ];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $message = (new SlackMessage())
            ->from('Launch Bug Sentinel')
            ->info()
            ->content('Daily bug triage digest');

        foreach ($this->reports as $report) {
            $message->attachment(function ($attachment) use ($report): void {
                $attachment
                    ->title($report->summary, route('admin.bug-reports.show', $report))
                    ->fields([
                        'Reference' => $report->reference,
                        'Priority' => ucfirst($report->priority),
                        'Status' => ucfirst(str_replace('_', ' ', $report->status)),
                    ]);
            });
        }

        if ($this->reports->isEmpty()) {
            $message->attachment(function ($attachment): void {
                $attachment->content('No open bugs require attention.');
            });
        }

        return $message;
    }
}
