<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\ConditionTimerEscalatedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ConditionTimerEscalationService
{
    public function __construct(private readonly AnalyticsRecorder $analytics)
    {
    }

    public function handle(Group $group, ?array $previous, array $current): void
    {
        $escalations = $this->detectEscalations($previous, $current);

        if ($escalations === []) {
            return;
        }

        $memberships = $group->memberships()
            ->with(['user.notificationPreference'])
            ->get();

        foreach ($escalations as $escalation) {
            $this->dispatchNotifications($group, $memberships, $escalation);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function detectEscalations(?array $previous, array $current): array
    {
        $previousIndex = $this->indexSummary($previous);
        $currentIndex = $this->indexSummary($current, includeMeta: true);
        $escalations = [];

        foreach ($currentIndex as $key => $snapshot) {
            $currentUrgency = $snapshot['urgency'];

            if (! in_array($currentUrgency, ['warning', 'critical'], true)) {
                continue;
            }

            $priorUrgency = $previousIndex[$key]['urgency'] ?? null;

            if ($priorUrgency !== null && $this->severityRank($currentUrgency) <= $this->severityRank($priorUrgency)) {
                continue;
            }

            $escalations[] = array_merge($snapshot, [
                'previous_urgency' => $priorUrgency,
            ]);
        }

        return $escalations;
    }

    protected function dispatchNotifications(Group $group, Collection $memberships, array $escalation): void
    {
        $allowedRoles = [
            GroupMembership::ROLE_OWNER,
            GroupMembership::ROLE_DUNGEON_MASTER,
            GroupMembership::ROLE_PLAYER,
        ];

        foreach ($memberships as $membership) {
            if (! in_array($membership->role, $allowedRoles, true)) {
                continue;
            }

            $user = $membership->user;

            if (! $user instanceof User) {
                continue;
            }

            $preference = $user->notificationPreference ?? NotificationPreference::forUser($user);
            $quiet = $this->isWithinQuietHours($user, $preference);

            $deliverInApp = (bool) $preference->channel_in_app;
            $deliverPush = (bool) $preference->channel_push;
            $deliverEmail = (bool) $preference->channel_email;
            $digestDelivery = $preference->digest_delivery ?? 'off';

            $hasChannel = $deliverInApp
                || ($deliverPush && ! $quiet)
                || ($deliverEmail && ! $quiet && $digestDelivery === 'off');

            if (! $hasChannel) {
                $this->analytics->record(
                    'timer_notification.skipped',
                    [
                        'reason' => 'no_channels',
                        'quiet_hours' => $quiet,
                        'digest_delivery' => $digestDelivery,
                        'urgency' => $escalation['urgency'],
                        'condition_key' => $escalation['condition']['key'] ?? null,
                    ],
                    actor: $user,
                    group: $group,
                );

                continue;
            }

            $payload = [
                'title' => $this->buildTitle($escalation),
                'body' => $this->buildBody($escalation),
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                ],
                'token' => $escalation['token'],
                'condition' => $escalation['condition'],
                'map' => $escalation['map'],
                'urgency' => $escalation['urgency'],
                'context_url' => route('groups.condition-timers.player-summary', ['group' => $group->id]),
            ];

            $user->notify(new ConditionTimerEscalatedNotification(
                $payload,
                $deliverInApp,
                $deliverPush,
                $deliverEmail,
                $quiet,
                $digestDelivery,
            ));

            $this->analytics->record(
                'timer_notification.dispatched',
                [
                    'urgency' => $escalation['urgency'],
                    'previous_urgency' => $escalation['previous_urgency'],
                    'condition_key' => $escalation['condition']['key'] ?? null,
                    'channels' => [
                        'in_app' => $deliverInApp,
                        'push' => $deliverPush && ! $quiet,
                        'email' => $deliverEmail && ! $quiet && $digestDelivery === 'off',
                    ],
                    'quiet_hours' => $quiet,
                    'digest_delivery' => $digestDelivery,
                ],
                actor: $user,
                group: $group,
            );
        }

        Log::info('condition_timer_escalation_dispatched', [
            'group_id' => $group->id,
            'escalation' => Arr::only($escalation, ['condition', 'urgency', 'token']),
        ]);
    }

    protected function isWithinQuietHours(User $user, NotificationPreference $preference): bool
    {
        if ($preference->quiet_hours_start === null || $preference->quiet_hours_end === null) {
            return false;
        }

        $timezone = $user->timezone ?: 'UTC';
        $now = CarbonImmutable::now($timezone);

        $start = CarbonImmutable::createFromFormat('H:i', $preference->quiet_hours_start, $timezone);
        $end = CarbonImmutable::createFromFormat('H:i', $preference->quiet_hours_end, $timezone);

        if (! $start || ! $end) {
            return false;
        }

        if ($start->equalTo($end)) {
            return true;
        }

        if ($start->lessThan($end)) {
            return $now->between($start, $end);
        }

        return $now->greaterThanOrEqualTo($start) || $now->lessThan($end);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function indexSummary(?array $summary, bool $includeMeta = false): array
    {
        if (! is_array($summary)) {
            return [];
        }

        $entries = $summary['entries'] ?? [];
        $index = [];

        foreach ($entries as $entry) {
            $tokenId = Arr::get($entry, 'token.id');
            $tokenLabel = Arr::get($entry, 'token.label');
            $tokenVisibility = Arr::get($entry, 'token.visibility');
            $conditions = $entry['conditions'] ?? [];

            foreach ($conditions as $condition) {
                $conditionKey = $condition['key'] ?? null;

                if ($tokenId === null || $conditionKey === null) {
                    continue;
                }

                $key = sprintf('%s:%s', $tokenId, $conditionKey);

                $index[$key] = [
                    'urgency' => $condition['urgency'] ?? 'calm',
                ];

                if ($includeMeta) {
                    $index[$key] = array_merge($index[$key], [
                        'token' => [
                            'id' => $tokenId,
                            'label' => $tokenLabel,
                            'visibility' => $tokenVisibility,
                        ],
                        'condition' => [
                            'key' => $conditionKey,
                            'label' => $condition['label'] ?? $conditionKey,
                            'rounds' => $condition['rounds'] ?? null,
                            'summary' => $condition['summary'] ?? null,
                            'rounds_hint' => $condition['rounds_hint'] ?? null,
                            'exposes_exact_rounds' => $condition['exposes_exact_rounds'] ?? null,
                        ],
                        'map' => $entry['map'] ?? null,
                    ]);
                }
            }
        }

        return $index;
    }

    protected function severityRank(string $urgency): int
    {
        return match ($urgency) {
            'critical' => 2,
            'warning' => 1,
            default => 0,
        };
    }

    protected function buildTitle(array $escalation): string
    {
        $token = Arr::get($escalation, 'token.label', 'Token');
        $condition = Arr::get($escalation, 'condition.label', 'Condition');
        $urgency = Arr::get($escalation, 'urgency', 'warning');

        return sprintf('%s • %s now %s', $token, $condition, $urgency);
    }

    protected function buildBody(array $escalation): string
    {
        $summary = Arr::get($escalation, 'condition.summary');
        $rounds = Arr::get($escalation, 'condition.rounds');
        $hint = Arr::get($escalation, 'condition.rounds_hint');

        $lines = array_filter([
            $summary,
            $rounds !== null ? sprintf('Rounds remaining: %s', $rounds) : null,
            $hint !== null ? sprintf('Time hint: %s', $hint) : null,
        ]);

        return implode(' ', $lines);
    }
}
