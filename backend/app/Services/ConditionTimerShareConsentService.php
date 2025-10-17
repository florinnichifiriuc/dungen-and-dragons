<?php

namespace App\Services;

use App\Exceptions\ConditionTimerShareConsentException;
use App\Models\ConditionTimerShareConsentLog;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class ConditionTimerShareConsentService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function currentStatuses(Group $group): Collection
    {
        $memberships = $group->memberships()
            ->with('user')
            ->orderBy('role')
            ->orderBy('id')
            ->get();

        $latestLogs = ConditionTimerShareConsentLog::query()
            ->where('group_id', $group->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id');

        return $memberships->map(function (GroupMembership $membership) use ($latestLogs) {
            $user = $membership->user;
            $log = $latestLogs->get($membership->user_id)?->first();

            return [
                'user_id' => $membership->user_id,
                'user_name' => $user?->name ?? Lang::get('Unknown adventurer'),
                'role' => $membership->role,
                'status' => $log?->action ?? 'unknown',
                'visibility' => $log?->visibility ?? null,
                'recorded_at' => $log?->created_at?->toIso8601String(),
                'recorded_by' => $log?->actor?->only(['id', 'name']),
            ];
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function auditTrail(Group $group, int $limit = 20): Collection
    {
        return ConditionTimerShareConsentLog::query()
            ->where('group_id', $group->id)
            ->with(['subject:id,name', 'actor:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (ConditionTimerShareConsentLog $log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'visibility' => $log->visibility,
                    'recorded_at' => $log->created_at?->toIso8601String(),
                    'notes' => $log->notes,
                    'source' => $log->source,
                    'subject' => $log->subject?->only(['id', 'name']),
                    'actor' => $log->actor?->only(['id', 'name']),
                ];
            });
    }

    public function recordConsent(
        Group $group,
        User $subject,
        ?User $actor,
        bool $consented,
        string $visibility,
        string $source = 'facilitator',
        ?string $notes = null
    ): ConditionTimerShareConsentLog {
        $visibility = $this->normalizeVisibility($visibility);

        return ConditionTimerShareConsentLog::query()->create([
            'group_id' => $group->id,
            'user_id' => $subject->getAuthIdentifier(),
            'recorded_by' => $actor?->getAuthIdentifier(),
            'action' => $consented ? 'granted' : 'revoked',
            'visibility' => $visibility,
            'source' => $source,
            'notes' => $notes,
        ]);
    }

    /**
     * @return array<int, int>
     */
    public function consentingUserIds(Group $group, string $visibility): array
    {
        $visibility = $this->normalizeVisibility($visibility);

        $latest = ConditionTimerShareConsentLog::query()
            ->select('user_id', DB::raw('MAX(id) as id'))
            ->where('group_id', $group->id)
            ->groupBy('user_id');

        $logs = ConditionTimerShareConsentLog::query()
            ->joinSub($latest, 'latest', fn ($join) => $join->on('latest.id', '=', 'condition_timer_share_consent_logs.id'))
            ->where('group_id', $group->id)
            ->where('action', 'granted')
            ->get();

        return $logs
            ->filter(function (ConditionTimerShareConsentLog $log) use ($visibility) {
                if ($log->visibility === 'details') {
                    return true;
                }

                if ($visibility === 'counts' && $log->visibility === 'counts') {
                    return true;
                }

                return false;
            })
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotForGroup(Group $group, string $visibility): array
    {
        $visibility = $this->normalizeVisibility($visibility);
        $granted = $this->ensureConsentForVisibility($group, $visibility);

        return [
            'visibility' => $visibility,
            'granted_user_ids' => $granted,
            'recorded_at' => now('UTC')->toIso8601String(),
        ];
    }

    /**
     * @return array<int, int>
     */
    public function ensureConsentForVisibility(Group $group, string $visibility): array
    {
        $visibility = $this->normalizeVisibility($visibility);
        $statuses = $this->currentStatuses($group);

        $players = $statuses->filter(fn ($entry) => Arr::get($entry, 'role') === GroupMembership::ROLE_PLAYER);

        $missing = $players->filter(function ($entry) use ($visibility) {
            if (Arr::get($entry, 'status') !== 'granted') {
                return true;
            }

            if ($visibility === 'counts') {
                return false;
            }

            return Arr::get($entry, 'visibility') === 'counts';
        });

        if ($missing->isNotEmpty()) {
            throw new ConditionTimerShareConsentException($missing);
        }

        return $players->pluck('user_id')->map(fn ($id) => (int) $id)->all();
    }

    protected function normalizeVisibility(string $visibility): string
    {
        return in_array($visibility, ['counts', 'details'], true) ? $visibility : 'counts';
    }
}
