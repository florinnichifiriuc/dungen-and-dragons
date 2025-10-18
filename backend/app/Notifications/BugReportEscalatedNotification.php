<?php

namespace App\Notifications;

use App\Models\BugReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BugReportEscalatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly BugReport $report)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if ($notifiable instanceof \App\Models\User) {
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $report = $this->report;

        return (new MailMessage())
            ->subject('[Bug] '.$report->summary.' ('.$report->priority.')')
            ->line('A new bug report requires attention.')
            ->line('Reference: '.$report->reference)
            ->line('Priority: '.$report->priority)
            ->line('Status: '.$report->status)
            ->line('Context: '.$report->context_type)
            ->action('Review bug report', route('admin.bug-reports.show', $report));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reference' => $this->report->reference,
            'summary' => $this->report->summary,
            'priority' => $this->report->priority,
            'status' => $this->report->status,
        ];
    }
}
