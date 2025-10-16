<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PlayerDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $digest
     * @param  array{in_app: bool, email: bool, push: bool}  $channels
     */
    public function __construct(private readonly array $digest, private readonly array $channels)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($this->channels['in_app']) {
            $channels[] = 'database';
        }

        if ($this->channels['push']) {
            $channels[] = 'broadcast';
        }

        if ($this->channels['email']) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->summaryLine(),
            'digest' => $this->digest,
            'channels' => $this->channels,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->title())
            ->greeting('Greetings adventurer!')
            ->line($this->summaryLine())
            ->line(sprintf('Window: %s → %s UTC', $this->digest['since'], $this->digest['until']))
            ->line(sprintf('Urgency: %s', Str::title($this->digest['urgency'] ?? 'calm')));

        $sections = Arr::get($this->digest, 'sections', []);

        $conditionEntries = Arr::get($sections, 'conditions', []);

        if ($conditionEntries !== []) {
            $mail->line('Condition highlights:');

            foreach ($conditionEntries as $entry) {
                $token = Arr::get($entry, 'token.label', 'Unknown token');
                $summary = Arr::get($entry, 'condition.summary');
                $mail->line(sprintf('- %s: %s', $token, $summary));
            }
        }

        $questEntries = Arr::get($sections, 'quests', []);

        if ($questEntries !== []) {
            $mail->line('Quest updates:');

            foreach ($questEntries as $entry) {
                $quest = Arr::get($entry, 'quest.title', 'Quest');
                $summary = Arr::get($entry, 'summary');
                $mail->line(sprintf('- %s — %s', $quest, $summary));
            }
        }

        $rewardEntries = Arr::get($sections, 'rewards', []);

        if ($rewardEntries !== []) {
            $mail->line('Loot & rewards:');

            foreach ($rewardEntries as $entry) {
                $reward = Arr::get($entry, 'reward.title', 'Reward');
                $recipient = Arr::get($entry, 'reward.awarded_to');
                $mail->line(sprintf('- %s%s', $reward, $recipient ? sprintf(' → %s', $recipient) : ''));
            }
        }

        if ($conditionEntries === [] && $questEntries === [] && $rewardEntries === []) {
            $mail->line('No changes were detected in this window. Rest easy until the next turn.');
        }

        $mail->action('Open campaign portal', url('/dashboard'));

        return $mail;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    protected function title(): string
    {
        $urgency = Str::title($this->digest['urgency'] ?? 'Calm');

        return sprintf('Player digest — %s cadence (%s)', $this->digest['mode'] ?? 'full', $urgency);
    }

    protected function summaryLine(): string
    {
        if (! ($this->digest['has_updates'] ?? false)) {
            return 'No updates in this window.';
        }

        $conditions = count(Arr::get($this->digest, 'sections.conditions', []));
        $quests = count(Arr::get($this->digest, 'sections.quests', []));
        $rewards = count(Arr::get($this->digest, 'sections.rewards', []));

        $parts = array_filter([
            $conditions > 0 ? sprintf('%d condition change%s', $conditions, $conditions === 1 ? '' : 's') : null,
            $quests > 0 ? sprintf('%d quest update%s', $quests, $quests === 1 ? '' : 's') : null,
            $rewards > 0 ? sprintf('%d reward log%s', $rewards, $rewards === 1 ? '' : 's') : null,
        ]);

        return $parts === [] ? 'Fresh tracks, but no major changes.' : implode(' · ', $parts);
    }
}
