<?php

namespace App\Services;

use App\Models\ConditionTimerSummaryShare;
use App\Models\ConditionTimerSummaryShareAccess;
use App\Models\Group;
use App\Models\GroupMembership;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

class ConditionTimerShareMaintenanceService
{
    public function __construct(
        private readonly ConditionTimerSummaryShareService $shares,
        private readonly ConditionTimerShareConsentService $consents
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildMaintenanceSnapshot(Group $group, ?ConditionTimerSummaryShare $share = null): array
    {
        $share ??= $this->shares->activeShareForGroup($group);
        $consentStatuses = $this->consents->currentStatuses($group);
        $playerStatuses = $consentStatuses->filter(
            fn (array $status) => Arr::get($status, 'role') === GroupMembership::ROLE_PLAYER
        );
        $pendingConsent = $playerStatuses->filter(
            fn (array $status) => Arr::get($status, 'status') !== 'granted'
        );

        $attentionReasons = [];
        $sharePayload = null;

        if ($pendingConsent->isNotEmpty()) {
            $attentionReasons[] = 'consent_missing';
        }

        if ($share) {
            $sharePayload = $this->buildSharePayload($share);
            $shareAttention = Arr::get($sharePayload, 'attention.reasons', []);

            if (is_array($shareAttention)) {
                $attentionReasons = array_merge($attentionReasons, $shareAttention);
            }
        }

        return [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'share' => $sharePayload,
            'consent' => [
                'pending' => $pendingConsent->values()->all(),
                'total_players' => $playerStatuses->count(),
            ],
            'attention' => [
                'needs_attention' => ! empty($attentionReasons),
                'reasons' => array_values(array_unique($attentionReasons)),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function attentionQueue(): array
    {
        return ConditionTimerSummaryShare::query()
            ->active()
            ->with('group')
            ->get()
            ->filter(fn (ConditionTimerSummaryShare $share) => $share->group !== null)
            ->map(function (ConditionTimerSummaryShare $share) {
                /** @var Group $group */
                $group = $share->group;

                return $this->buildMaintenanceSnapshot($group, $share);
            })
            ->filter(fn (array $snapshot) => Arr::get($snapshot, 'attention.needs_attention') === true)
            ->sortBy(fn (array $snapshot) => Arr::get($snapshot, 'group.name'))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSharePayload(ConditionTimerSummaryShare $share): array
    {
        $state = $this->shares->describeShareState($share);
        $windowDays = max(1, (int) config('condition-transparency.maintenance.access_window_days', 7));
        $quietThreshold = max(0, (float) config('condition-transparency.maintenance.quiet_hour_attention_ratio', 0.4));
        $expiryHours = max(1, (int) config('condition-transparency.maintenance.expiry_attention_hours', 24));

        $windowEnd = CarbonImmutable::now('UTC');
        $windowStart = $windowEnd->subDays($windowDays - 1)->startOfDay();

        $eventsQuery = ConditionTimerSummaryShareAccess::query()
            ->forShare($share)
            ->forEvent('access')
            ->between($windowStart, $windowEnd);

        $totalAccesses = (clone $eventsQuery)->count();
        $quietAccesses = (clone $eventsQuery)->quietHours()->count();

        $quietRatio = $totalAccesses > 0 ? $quietAccesses / $totalAccesses : 0.0;
        $lastAccess = ConditionTimerSummaryShareAccess::query()
            ->forShare($share)
            ->forEvent('access')
            ->orderByDesc('occurred_at')
            ->first();

        $reasons = [];

        if (Arr::get($state, 'redacted') === true) {
            $reasons[] = 'redacted';
        }

        if ($share->isExpired()) {
            $reasons[] = 'expired';
        } elseif ($share->expiresWithinHours($expiryHours)) {
            $reasons[] = 'expiring_soon';
        }

        if ($quietThreshold > 0 && $quietRatio >= $quietThreshold) {
            $reasons[] = 'excessive_quiet_hour_access';
        }

        return [
            'id' => $share->id,
            'state' => $state,
            'expires_at' => $share->expires_at?->toIso8601String(),
            'access_count' => (int) $share->access_count,
            'last_accessed_at' => $lastAccess?->occurred_at?->toIso8601String(),
            'quiet_hour_ratio' => $quietRatio,
            'attention' => [
                'needs_attention' => ! empty($reasons),
                'reasons' => array_values(array_unique($reasons)),
            ],
        ];
    }
}
