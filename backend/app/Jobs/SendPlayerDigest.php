<?php

namespace App\Jobs;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\PlayerDigestNotification;
use App\Services\PlayerDigestService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPlayerDigest implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public $backoff = [60, 300, 900];

    /**
     * The maximum number of attempts.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $userId, private readonly bool $force = false)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(PlayerDigestService $digests): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null) {
            return;
        }

        $preferences = NotificationPreference::forUser($user);

        if ($preferences->digest_delivery === 'off') {
            return;
        }

        $now = CarbonImmutable::now('UTC');

        if (! $this->force && $this->isWithinQuietHours($preferences, $now)) {
            if ($this->job !== null) {
                $this->release(600);
            }

            return;
        }

        $mode = $preferences->digest_delivery === 'urgent' ? 'urgent' : 'full';
        $since = $this->resolveSince($preferences, $now);

        $digest = $digests->build($user, $since, $mode);

        if (! ($digest['has_updates'] ?? false)) {
            return;
        }

        $channels = [
            'in_app' => (bool) $preferences->digest_channel_in_app,
            'email' => (bool) $preferences->digest_channel_email,
            'push' => (bool) $preferences->digest_channel_push,
        ];

        if (! $channels['in_app'] && ! $channels['email'] && ! $channels['push']) {
            return;
        }

        $user->notify(new PlayerDigestNotification($digest, $channels));

        $preferences->forceFill([
            'digest_last_sent_at' => $now,
        ])->save();
    }

    protected function resolveSince(NotificationPreference $preferences, CarbonImmutable $now): CarbonImmutable
    {
        $lastSent = $preferences->digest_last_sent_at;

        if ($lastSent instanceof CarbonImmutable) {
            if ($lastSent->greaterThan($now)) {
                return $now;
            }

            return $lastSent;
        }

        return match ($preferences->digest_delivery) {
            'urgent' => $now->subHours(6),
            'session' => $now->subHours(12),
            'daily' => $now->subDay(),
            default => $now->subDay(),
        };
    }

    protected function isWithinQuietHours(NotificationPreference $preferences, CarbonImmutable $now): bool
    {
        $start = $this->normalizeTime($preferences->quiet_hours_start, $now);
        $end = $this->normalizeTime($preferences->quiet_hours_end, $now);

        if ($start === null || $end === null) {
            return false;
        }

        if ($start->equalTo($end)) {
            return false;
        }

        if ($start->lessThan($end)) {
            return $now->betweenIncluded($start, $end);
        }

        return $now->greaterThanOrEqualTo($start) || $now->lessThan($end->addDay());
    }

    protected function normalizeTime(?string $time, CarbonImmutable $now): ?CarbonImmutable
    {
        if ($time === null || $time === '') {
            return null;
        }

        $format = strlen($time) === 5 ? 'H:i' : 'H:i:s';

        $parsed = CarbonImmutable::createFromFormat($format, $time, 'UTC');

        if ($parsed === false) {
            return null;
        }

        return $parsed->setDate($now->year, $now->month, $now->day);
    }
}
