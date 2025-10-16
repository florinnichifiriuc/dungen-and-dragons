<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConditionTimerEscalatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        protected array $payload,
        protected bool $deliverInApp,
        protected bool $deliverPush,
        protected bool $deliverEmail,
        protected bool $quietSuppressed,
        protected string $digestDelivery
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($this->deliverInApp) {
            $channels[] = 'database';
        }

        if ($this->deliverPush && ! $this->quietSuppressed) {
            $channels[] = 'broadcast';
        }

        if ($this->deliverEmail && ! $this->quietSuppressed && $this->digestDelivery === 'off') {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->payload['title'] ?? 'Condition timer escalated',
            'body' => $this->payload['body'] ?? null,
            'group' => $this->payload['group'] ?? null,
            'token' => $this->payload['token'] ?? null,
            'condition' => $this->payload['condition'] ?? null,
            'urgency' => $this->payload['urgency'] ?? null,
            'context_url' => $this->payload['context_url'] ?? null,
            'quiet_suppressed' => $this->quietSuppressed,
            'digest_delivery' => $this->digestDelivery,
            'channels' => [
                'in_app' => $this->deliverInApp,
                'push' => $this->deliverPush,
                'email' => $this->deliverEmail,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject($this->payload['title'] ?? 'Condition timer escalation')
            ->line($this->payload['body'] ?? '')
            ->action('Review condition timers', $this->payload['context_url'] ?? url('/'));
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
