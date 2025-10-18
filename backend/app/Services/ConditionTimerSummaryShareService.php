<?php

namespace App\Services;

use App\Models\ConditionTimerSummaryShare;
use App\Models\ConditionTimerSummaryShareAccess;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConditionTimerSummaryShareService
{
    public function __construct(
        private readonly ConditionTimerShareConsentService $consents,
        private readonly int $defaultTtlDays = 14
    ) {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function presetBundles(): array
    {
        $bundles = (array) config('condition-transparency.share_links.bundles', []);

        return collect($bundles)
            ->map(function (array $bundle, string $key) {
                return array_merge([
                    'key' => $key,
                    'label' => $bundle['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                    'description' => $bundle['description'] ?? null,
                    'expiry_preset' => $bundle['expiry_preset'] ?? '24h',
                    'visibility_mode' => $bundle['visibility_mode'] ?? 'counts',
                ], $bundle);
            })
            ->all();
    }

    public function bundle(string $key): ?array
    {
        $bundles = $this->presetBundles();

        return $bundles[$key] ?? null;
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

    public function createShareForGroup(
        Group $group,
        User $creator,
        ?CarbonImmutable $expiresAt = null,
        string $visibilityMode = 'counts',
        bool $neverExpires = false,
        ?string $presetKey = null
    ): ConditionTimerSummaryShare
    {
        $now = CarbonImmutable::now('UTC');

        ConditionTimerSummaryShare::query()
            ->where('group_id', $group->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $now]);

        if (! $neverExpires && $expiresAt === null) {
            $expiresAt = $now->addDays($this->defaultTtlDays);
        }

        $visibilityMode = in_array($visibilityMode, ['counts', 'details'], true) ? $visibilityMode : 'counts';
        $consentSnapshot = $this->consents->snapshotForGroup($group, $visibilityMode);

        $share = ConditionTimerSummaryShare::create([
            'group_id' => $group->id,
            'created_by' => $creator->getAuthIdentifier(),
            'token' => Str::random(48),
            'expires_at' => $neverExpires ? null : $expiresAt,
            'visibility_mode' => $visibilityMode,
            'preset_key' => $presetKey,
            'consent_snapshot' => $consentSnapshot,
        ]);

        $this->logAccessEvent($share, 'created', [
            'user_id' => $creator->getAuthIdentifier(),
            'preset_key' => $presetKey,
            'visibility_mode' => $visibilityMode,
        ]);

        return $share;
    }

    public function revokeShare(ConditionTimerSummaryShare $share, ?User $actor = null): void
    {
        if ($share->deleted_at !== null) {
            return;
        }

        $share->deleted_at = CarbonImmutable::now('UTC');
        $share->save();

        $this->logAccessEvent($share, 'revocation', [
            'user_id' => $actor?->getAuthIdentifier(),
        ]);
    }

    public function extendShare(
        ConditionTimerSummaryShare $share,
        ?CarbonImmutable $expiresAt,
        bool $neverExpires = false,
        ?User $actor = null
    ): void {
        $share->forceFill([
            'expires_at' => $neverExpires ? null : $expiresAt,
        ])->save();

        $this->logAccessEvent($share, 'extension', [
            'expires_at' => $share->expires_at?->toIso8601String(),
            'user_id' => $actor?->getAuthIdentifier(),
        ]);
    }

    public function recordAccess(ConditionTimerSummaryShare $share, Request $request): void
    {
        $ipAddress = $request->ip();
        $ipHash = $ipAddress ? $this->hashValue($ipAddress) : null;
        $userAgent = $request->userAgent();
        $userAgentHash = $userAgent ? $this->hashValue($userAgent) : null;
        $quietHourSuppressed = $request->boolean('quiet_hour_suppressed', false);

        $share->forceFill([
            'access_count' => (int) $share->access_count + 1,
            'last_accessed_at' => CarbonImmutable::now('UTC'),
        ])->save();

        $this->logAccessEvent($share, 'access', [
            'ip_hash' => $ipHash,
            'user_agent_hash' => $userAgentHash,
            'quiet_hour_suppressed' => $quietHourSuppressed,
            'user_id' => $request->user()?->getAuthIdentifier(),
        ]);

        Log::info('condition_timer_share_access_recorded', [
            'share_id' => $share->id,
            'group_id' => $share->group_id,
            'ip_hash' => $ipHash,
            'user_agent_hash' => $userAgentHash,
            'quiet_hour_suppressed' => $quietHourSuppressed,
        ]);
    }

    public function recordSyntheticPing(
        ConditionTimerSummaryShare $share,
        bool $successful,
        ?float $durationMs = null,
        ?int $status = null
    ): void {
        $this->logAccessEvent($share, 'monitor', [
            'successful' => $successful,
            'duration_ms' => $durationMs,
            'status' => $status,
        ]);

        if (! $successful) {
            Log::warning('condition_timer_share_monitor_failed', [
                'share_id' => $share->id,
                'group_id' => $share->group_id,
                'status' => $status,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function describeShareState(ConditionTimerSummaryShare $share): array
    {
        $now = CarbonImmutable::now('UTC');
        $expiresAt = $share->expires_at;
        $state = 'active';
        $redacted = false;
        $relative = null;

        if ($expiresAt === null) {
            $state = 'evergreen';
        } else {
            $relative = $expiresAt->diffForHumans($now, ['parts' => 2, 'short' => true]);

            if ($expiresAt->isPast()) {
                $state = 'expired';

                if ($expiresAt->addHours(48)->isPast()) {
                    $redacted = true;
                }
            } elseif ($expiresAt->diffInHours($now) <= 24) {
                $state = 'expiring_soon';
            }
        }

        $preset = null;

        if ($share->preset_key) {
            $bundle = $this->bundle($share->preset_key);
            $preset = [
                'key' => $share->preset_key,
                'label' => $bundle['label'] ?? $share->preset_key,
                'description' => $bundle['description'] ?? null,
            ];
        }

        return [
            'state' => $state,
            'relative' => $relative,
            'redacted' => $redacted,
            'preset' => $preset,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function accessTrend(ConditionTimerSummaryShare $share, int $days = 7): array
    {
        $days = max(1, $days);
        $now = CarbonImmutable::now('UTC')->endOfDay();
        $start = $now->subDays($days - 1)->startOfDay();

        $rows = ConditionTimerSummaryShareAccess::query()
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) as total')
            ->where('condition_timer_summary_share_id', $share->id)
            ->where('event_type', 'access')
            ->whereBetween('occurred_at', [$start, $now])
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    CarbonImmutable::parse($row->day, 'UTC')->toDateString() => (int) $row->total,
                ];
            });

        $trend = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->addDays($i);
            $trend[] = [
                'date' => $date->toDateString(),
                'count' => $rows->get($date->toDateString(), 0),
            ];
        }

        return $trend;
    }

    /**
     * @return array<string, mixed>
     */
    public function insights(ConditionTimerSummaryShare $share, Group $group, int $days = 7): array
    {
        $trend = $this->accessTrend($share, $days);
        $total = collect($trend)->sum('count');
        $peak = collect($trend)->sortByDesc('count')->first();
        $bundle = $share->preset_key ? $this->bundle($share->preset_key) : null;

        $extensions = ConditionTimerSummaryShareAccess::query()
            ->where('condition_timer_summary_share_id', $share->id)
            ->where('event_type', 'extension')
            ->orderByDesc('occurred_at')
            ->get();

        $actorRoles = [];

        if ($extensions->isNotEmpty()) {
            $actors = $extensions->pluck('user_id')->filter()->unique();

            if ($actors->isNotEmpty()) {
                $actorRoles = GroupMembership::query()
                    ->where('group_id', $group->id)
                    ->whereIn('user_id', $actors)
                    ->pluck('role', 'user_id')
                    ->all();
            }
        }

        $roleLabel = function (?string $role): string {
            return $role ?? 'unknown';
        };

        $extensionActors = $extensions
            ->groupBy(function (ConditionTimerSummaryShareAccess $access) use ($actorRoles, $roleLabel) {
                return $roleLabel($actorRoles[$access->user_id] ?? null);
            })
            ->map(function (Collection $events, string $role) {
                return [
                    'role' => $role,
                    'count' => $events->count(),
                ];
            })
            ->values()
            ->all();

        $recentExtensions = $extensions
            ->take(5)
            ->map(function (ConditionTimerSummaryShareAccess $access) use ($actorRoles, $roleLabel) {
                return [
                    'occurred_at' => $access->occurred_at?->toIso8601String(),
                    'actor_role' => $roleLabel($actorRoles[$access->user_id] ?? null),
                    'expires_at' => Arr::get($access->metadata, 'expires_at'),
                ];
            })
            ->values()
            ->all();

        $peakExtensions = [];

        if ($peak && ($peak['count'] ?? 0) > 0 && ($peakDate = Arr::get($peak, 'date'))) {
            try {
                $day = CarbonImmutable::parse($peakDate, 'UTC');
                $peakExtensions = ConditionTimerSummaryShareAccess::query()
                    ->where('condition_timer_summary_share_id', $share->id)
                    ->where('event_type', 'extension')
                    ->whereBetween('occurred_at', [$day->subDay()->startOfDay(), $day->endOfDay()])
                    ->get()
                    ->map(function (ConditionTimerSummaryShareAccess $access) use ($actorRoles, $roleLabel) {
                        return $roleLabel($actorRoles[$access->user_id] ?? null);
                    })
                    ->unique()
                    ->values()
                    ->all();
            } catch (\Throwable) {
                $peakExtensions = [];
            }
        }

        $presetDistribution = ConditionTimerSummaryShare::query()
            ->where('group_id', $group->id)
            ->selectRaw('COALESCE(preset_key, "custom") as preset_key, COUNT(*) as total')
            ->groupBy('preset_key')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                return [
                    'preset_key' => $row->preset_key,
                    'total' => (int) $row->total,
                ];
            })
            ->map(function (array $entry) {
                $bundle = $this->bundle($entry['preset_key']);
                $label = $bundle['label'] ?? ($entry['preset_key'] === 'custom' ? 'Custom configuration' : $entry['preset_key']);

                return [
                    'preset_key' => $entry['preset_key'],
                    'label' => $label,
                    'total' => $entry['total'],
                ];
            })
            ->values()
            ->all();

        return [
            'trend' => $trend,
            'totals' => [
                'week' => $total,
            ],
            'peak' => $peak ? [
                'date' => $peak['date'],
                'count' => $peak['count'],
                'bundle_key' => $share->preset_key,
                'bundle_label' => $bundle['label'] ?? null,
                'extension_roles' => $peakExtensions,
            ] : null,
            'extension_actors' => $extensionActors,
            'recent_extensions' => $recentExtensions,
            'preset_distribution' => $presetDistribution,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportAccessTrails(Group $group): array
    {
        $shares = ConditionTimerSummaryShare::query()
            ->withTrashed()
            ->where('group_id', $group->id)
            ->with(['accesses' => function ($query): void {
                $query->orderByDesc('occurred_at');
            }])
            ->orderByDesc('created_at')
            ->get();

        return $shares->map(function (ConditionTimerSummaryShare $share) {
            $state = $this->describeShareState($share);
            $tokenSuffix = substr($share->token, -8);

            return [
                'token_suffix' => $tokenSuffix,
                'visibility_mode' => $share->visibility_mode,
                'preset_key' => $share->preset_key,
                'created_at' => $share->created_at?->toIso8601String(),
                'expires_at' => $share->expires_at?->toIso8601String(),
                'deleted_at' => $share->deleted_at?->toIso8601String(),
                'status' => $state,
                'access_count' => (int) $share->access_count,
                'events' => $share->accesses->map(function (ConditionTimerSummaryShareAccess $access) {
                    return [
                        'event_type' => $access->event_type,
                        'occurred_at' => $access->occurred_at?->toIso8601String(),
                        'ip_hash' => $access->ip_hash ? substr($access->ip_hash, 0, 24) : null,
                        'user_agent_hash' => $access->user_agent_hash ? substr($access->user_agent_hash, 0, 24) : null,
                        'user_id' => $access->user_id,
                        'quiet_hour_suppressed' => (bool) $access->quiet_hour_suppressed,
                        'metadata' => $access->metadata,
                    ];
                })->take(50)->values()->all(),
            ];
        })->values()->all();
    }

    protected function hashValue(string $value): string
    {
        $salt = config('app.key');

        return hash('sha256', $value.'|'.$salt);
    }

    protected function logAccessEvent(ConditionTimerSummaryShare $share, string $eventType, array $attributes = []): void
    {
        ConditionTimerSummaryShareAccess::query()->create([
            'condition_timer_summary_share_id' => $share->id,
            'event_type' => $eventType,
            'occurred_at' => CarbonImmutable::now('UTC'),
            'ip_hash' => Arr::get($attributes, 'ip_hash'),
            'user_agent_hash' => Arr::get($attributes, 'user_agent_hash'),
            'user_id' => Arr::get($attributes, 'user_id'),
            'quiet_hour_suppressed' => (bool) Arr::get($attributes, 'quiet_hour_suppressed', false),
            'metadata' => Arr::except($attributes, ['ip_hash', 'user_agent_hash', 'user_id', 'quiet_hour_suppressed']),
        ]);
    }
}
