<?php

namespace App\Services;

use App\Models\ConditionTimerSummaryShare;
use App\Models\ConditionTimerSummaryShareAccess;
use App\Models\Group;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ConditionTimerSummaryShareService
{
    public function __construct(private readonly int $defaultTtlDays = 14)
    {
    }

    public function activeShareForGroup(Group $group): ?ConditionTimerSummaryShare
    {
        $now = CarbonImmutable::now('UTC');

        return ConditionTimerSummaryShare::query()
            ->where('group_id', $group->id)
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->orderByDesc('created_at')
            ->first();
    }

    public function createShareForGroup(Group $group, User $creator, ?CarbonImmutable $expiresAt = null): ConditionTimerSummaryShare
    {
        $now = CarbonImmutable::now('UTC');

        ConditionTimerSummaryShare::query()
            ->where('group_id', $group->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $now]);

        if ($expiresAt === null) {
            $expiresAt = $now->addDays($this->defaultTtlDays);
        }

        return ConditionTimerSummaryShare::create([
            'group_id' => $group->id,
            'created_by' => $creator->getAuthIdentifier(),
            'token' => Str::random(48),
            'expires_at' => $expiresAt,
        ]);
    }

    public function revokeShare(ConditionTimerSummaryShare $share): void
    {
        if ($share->deleted_at !== null) {
            return;
        }

        $share->deleted_at = CarbonImmutable::now('UTC');
        $share->save();
    }

    public function extendShare(ConditionTimerSummaryShare $share, CarbonImmutable $expiresAt): ConditionTimerSummaryShare
    {
        $share->expires_at = $expiresAt;
        $share->save();

        return $share->refresh();
    }

    public function recordAccess(
        ConditionTimerSummaryShare $share,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ConditionTimerSummaryShareAccess {
        return $share->accessLogs()->create([
            'accessed_at' => CarbonImmutable::now('UTC'),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function presentShareForManagers(ConditionTimerSummaryShare $share): array
    {
        $stats = $this->accessStatistics($share);

        $expiry = $this->shareExpiryMetadata($share->expires_at);

        return [
            'id' => $share->id,
            'url' => route('shares.condition-timers.player-summary.show', $share->token),
            'created_at' => $share->created_at?->toIso8601String(),
            'expires_at' => $share->expires_at?->toIso8601String(),
            'expiry' => $expiry,
            'stats' => [
                'total_views' => $stats['total_views'],
                'last_accessed_at' => $stats['last_accessed_at']?->toIso8601String(),
                'recent_accesses' => $stats['recent_accesses']->map(
                    fn (ConditionTimerSummaryShareAccess $access): array => [
                        'id' => $access->id,
                        'accessed_at' => $access->accessed_at?->toIso8601String(),
                        'ip_address' => $this->maskIpAddress($access->ip_address),
                        'user_agent' => $access->user_agent ? Str::limit($access->user_agent, 120) : null,
                    ],
                )->all(),
                'daily_views' => $stats['daily_views']->map(
                    fn (array $day): array => [
                        'date' => $day['date']->toDateString(),
                        'total' => $day['total'],
                    ],
                )->all(),
            ],
        ];
    }

    public function presentShareForExport(ConditionTimerSummaryShare $share): array
    {
        $stats = $this->accessStatistics($share);

        $expiry = $this->shareExpiryMetadata($share->expires_at);

        return [
            'url' => route('shares.condition-timers.player-summary.show', $share->token),
            'created_at' => $share->created_at?->clone()->setTimezone('UTC'),
            'expires_at' => $share->expires_at?->clone()->setTimezone('UTC'),
            'expiry' => $expiry,
            'stats' => [
                'total_views' => $stats['total_views'],
                'last_accessed_at' => $stats['last_accessed_at'],
                'recent_accesses' => $stats['recent_accesses']->map(
                    fn (ConditionTimerSummaryShareAccess $access): array => [
                        'id' => $access->id,
                        'accessed_at' => $access->accessed_at?->clone()->setTimezone('UTC'),
                        'ip_address' => $this->maskIpAddress($access->ip_address),
                        'user_agent' => $access->user_agent ? Str::limit($access->user_agent, 120) : null,
                    ],
                )->all(),
                'daily_views' => $stats['daily_views']->map(
                    fn (array $day): array => [
                        'date' => $day['date'],
                        'total' => $day['total'],
                    ],
                )->all(),
            ],
        ];
    }

    /**
     * @return array{
     *     total_views: int,
     *     last_accessed_at: CarbonImmutable|null,
     *     recent_accesses: \Illuminate\Support\Collection<int, ConditionTimerSummaryShareAccess>,
     *     daily_views: \Illuminate\Support\Collection<int, array{date: CarbonImmutable, total: int}>
     * }
     */
    private function accessStatistics(ConditionTimerSummaryShare $share): array
    {
        $aggregate = ConditionTimerSummaryShareAccess::query()
            ->selectRaw('COUNT(*) as total_views')
            ->selectRaw('MAX(accessed_at) as last_accessed_at')
            ->where('condition_timer_summary_share_id', $share->id)
            ->first();

        $lastAccessedAt = null;

        if ($aggregate && $aggregate->last_accessed_at) {
            $lastAccessedAt = CarbonImmutable::parse($aggregate->last_accessed_at, 'UTC');
        }

        $recentAccesses = $share->accessLogs()
            ->latest('accessed_at')
            ->limit(5)
            ->get();

        $totalViews = $aggregate?->total_views ?? 0;

        $now = CarbonImmutable::now('UTC');
        $windowStart = $now->subDays(6)->startOfDay();

        $dailyTemplate = collect(range(0, 6))->map(
            function (int $offset) use ($now) {
                $date = $now->subDays(6 - $offset)->startOfDay();

                return [
                    'date' => $date,
                    'total' => 0,
                ];
            }
        )->keyBy(fn (array $day) => $day['date']->toDateString());

        $windowAccesses = ConditionTimerSummaryShareAccess::query()
            ->where('condition_timer_summary_share_id', $share->id)
            ->where('accessed_at', '>=', $windowStart)
            ->get();

        foreach ($windowAccesses as $access) {
            if (! $access->accessed_at) {
                continue;
            }

            $dateKey = $access->accessed_at->clone()->setTimezone('UTC')->startOfDay()->toDateString();

            if ($dailyTemplate->has($dateKey)) {
                $dailyTemplate[$dateKey]['total']++;
            }
        }

        return [
            'total_views' => (int) $totalViews,
            'last_accessed_at' => $lastAccessedAt,
            'recent_accesses' => $recentAccesses,
            'daily_views' => $dailyTemplate->values(),
        ];
    }

    private function maskIpAddress(?string $ipAddress): ?string
    {
        if ($ipAddress === null) {
            return null;
        }

        if (str_contains($ipAddress, ':')) {
            $segments = explode(':', $ipAddress);

            if (count($segments) > 4) {
                $segments = array_slice($segments, 0, 4);
            }

            return implode(':', $segments) . '::';
        }

        $octets = explode('.', $ipAddress);

        if (count($octets) === 4) {
            return sprintf('%s.%s.%s.*', $octets[0], $octets[1], $octets[2]);
        }

        return $ipAddress;
    }

    private function shareExpiryMetadata(?CarbonImmutable $expiresAt): array
    {
        if ($expiresAt === null) {
            return [
                'state' => 'no_expiry',
                'label' => 'Link does not expire automatically',
                'remaining_hours' => null,
            ];
        }

        $now = CarbonImmutable::now('UTC');
        $secondsRemaining = $expiresAt->getTimestamp() - $now->getTimestamp();
        $hoursRemaining = $secondsRemaining > 0 ? $secondsRemaining / 3600 : 0.0;

        if ($secondsRemaining <= 0) {
            return [
                'state' => 'expired',
                'label' => 'Link has expired',
                'remaining_hours' => 0.0,
            ];
        }

        if ($hoursRemaining <= 24) {
            return [
                'state' => 'expiring_24h',
                'label' => 'Expires within 24 hours',
                'remaining_hours' => $hoursRemaining,
            ];
        }

        if ($hoursRemaining <= 48) {
            return [
                'state' => 'expiring_48h',
                'label' => 'Expires within 48 hours',
                'remaining_hours' => $hoursRemaining,
            ];
        }

        return [
            'state' => 'active',
            'label' => 'Link is active',
            'remaining_hours' => $hoursRemaining,
        ];
    }
}
