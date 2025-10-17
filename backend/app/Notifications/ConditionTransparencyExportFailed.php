<?php

namespace App\Notifications;

use App\Models\ConditionTransparencyExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConditionTransparencyExportFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ConditionTransparencyExport $export)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Condition transparency export failed')
            ->greeting('Heads up!')
            ->line('We could not finish the condition transparency export you requested.')
            ->line('Reason: '.($this->export->failure_reason ?: 'Unknown issue'))
            ->line('The request has been marked as failed. You can trigger a new export from the group condition summary page.');
    }
}
