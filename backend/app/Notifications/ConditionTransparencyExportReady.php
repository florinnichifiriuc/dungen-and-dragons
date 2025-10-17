<?php

namespace App\Notifications;

use App\Models\ConditionTransparencyExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class ConditionTransparencyExportReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ConditionTransparencyExport $export, private readonly string $downloadUrl)
    {
    }

    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['slack'];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Condition transparency export ready')
            ->greeting('Hey adventurer!')
            ->line('The latest condition transparency export you requested is ready to download.')
            ->line(sprintf('Format: %s • Visibility: %s', strtoupper($this->export->format), $this->export->visibility_mode))
            ->action('Download export', $this->downloadUrl)
            ->line('This link requires facilitator access to your group to open.');
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage())
            ->success()
            ->content(sprintf(
                'Condition transparency export ready for %s (%s visibility).',
                $this->export->group?->name ?? 'a group',
                $this->export->visibility_mode
            ))
            ->attachment(function ($attachment): void {
                $attachment->title('Download export', $this->downloadUrl)
                    ->fields([
                        'Format' => strtoupper($this->export->format),
                        'Export #' => (string) $this->export->id,
                    ]);
            });
    }
}
