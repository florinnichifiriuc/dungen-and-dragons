<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignQuestUpdate;
use App\Models\CampaignRoleAssignment;
use App\Models\ConditionTimerAdjustment;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\MapToken;
use App\Models\SessionReward;
use App\Models\User;
use App\Support\ConditionSummaryCopy;
use App\Support\ConditionTimerInsights;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PlayerDigestService
{
    public function __construct(private readonly ConditionMentorBriefingService $mentorBriefings)
    {
    }

    /**
     * Build a digest payload for the given user.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function build(User $user, ?CarbonImmutable $since = null, string $mode = 'full', array $context = []): array
    {
        $now = CarbonImmutable::now('UTC');
        $since = $since ?? $now->subDay();

        $campaignId = Arr::get($context, 'campaign_id');
        $groupIds = GroupMembership::query()
            ->where('user_id', $user->id)
            ->pluck('group_id')
            ->map(fn (int $id) => $id)
            ->values();

        if ($campaignId !== null) {
            $campaign = Campaign::query()->find($campaignId);

            if ($campaign !== null) {
                $groupIds = $groupIds->filter(fn (int $groupId) => $groupId === $campaign->group_id)->values();
            }
        }

        $campaignIds = CampaignRoleAssignment::query()
            ->where('assignee_type', User::class)
            ->where('assignee_id', $user->id)
            ->where('status', 'active')
            ->pluck('campaign_id')
            ->map(fn (int $id) => $id)
            ->unique()
            ->values();

        if ($campaignId !== null) {
            $campaignIds = $campaignIds->filter(fn (int $id) => $id === (int) $campaignId)->values();
        }

        $conditions = $this->collectConditionHighlights($groupIds, $since);
        $quests = $this->collectQuestUpdates($campaignIds, $groupIds, $since);
        $rewards = $this->collectRewardUpdates($campaignIds, $since);
        $mentorCatchUps = $this->collectMentorCatchUps($groupIds, $since);

        if ($mode === 'urgent') {
            $conditions = $conditions->filter(function (array $entry): bool {
                return Arr::get($entry, 'condition.urgency') === 'critical';
            })->values();
            $quests = collect();
            $rewards = collect();
        }

        $hasUpdates = $conditions->isNotEmpty() || $quests->isNotEmpty() || $rewards->isNotEmpty();

        $digestUrgency = $this->resolveDigestUrgency($conditions);

        $mentorTip = null;

        if ($mentorCatchUps->isNotEmpty()) {
            $mentorTip = [
                'count' => $mentorCatchUps->count(),
                'summary' => trans_choice('condition_timers.share_view.catch_up.summary', $mentorCatchUps->count(), [
                    'count' => $mentorCatchUps->count(),
                ]),
                'items' => $mentorCatchUps->take(5)->map(function (array $entry) {
                    return [
                        'id' => $entry['id'],
                        'excerpt' => $entry['excerpt'],
                        'delivered_at' => $entry['delivered_at'],
                        'focus_summary' => $entry['focus_summary'],
                    ];
                })->values()->all(),
            ];
        }

        return [
            'user_id' => $user->id,
            'mode' => $mode,
            'generated_at' => $now->toIso8601String(),
            'since' => $since->toIso8601String(),
            'until' => $now->toIso8601String(),
            'urgency' => $digestUrgency,
            'has_updates' => $hasUpdates,
            'sections' => [
                'conditions' => $conditions->all(),
                'quests' => $quests->all(),
                'rewards' => $rewards->all(),
            ],
            'mentor_tip' => $mentorTip,
            'markdown' => $this->renderMarkdown(
                $user,
                $now,
                $since,
                $conditions,
                $quests,
                $rewards,
                $mentorCatchUps,
                $digestUrgency
            ),
        ];
    }

    /**
     * @param  Collection<int, int>  $groupIds
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectMentorCatchUps(Collection $groupIds, CarbonImmutable $since): Collection
    {
        if ($groupIds->isEmpty()) {
            return collect();
        }

        $groups = Group::query()->whereIn('id', $groupIds)->get();

        return $groups
            ->flatMap(function (Group $group) use ($since) {
                return $this->mentorBriefings->catchUpPrompts($group, $since);
            })
            ->filter(function ($entry) {
                return is_array($entry) && ($entry['excerpt'] ?? null) !== null;
            })
            ->unique('id')
            ->sortByDesc(function (array $entry) {
                $timestamp = $entry['delivered_at'] ?? null;

                if (! $timestamp) {
                    return 0;
                }

                try {
                    return CarbonImmutable::parse($timestamp, 'UTC')->getTimestamp();
                } catch (\Throwable) {
                    return 0;
                }
            })
            ->values();
    }

    /**
     * @param  Collection<int, int>  $groupIds
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectConditionHighlights(Collection $groupIds, CarbonImmutable $since): Collection
    {
        if ($groupIds->isEmpty()) {
            return collect();
        }

        return ConditionTimerAdjustment::query()
            ->whereIn('group_id', $groupIds)
            ->where('recorded_at', '>=', $since)
            ->with([
                'group:id,name',
                'token:id,map_id,name,faction,hidden',
                'token.map:id,title,group_id',
            ])
            ->orderByDesc('recorded_at')
            ->get()
            ->map(function (ConditionTimerAdjustment $adjustment): array {
                $token = $adjustment->token;
                $map = $token?->map;
                $group = $adjustment->group;

                $urgency = $this->resolveUrgency($adjustment->new_rounds);

                return [
                    'group' => [
                        'id' => $group?->id,
                        'name' => $group?->name,
                    ],
                    'map' => $map ? [
                        'id' => $map->id,
                        'title' => $map->title,
                    ] : null,
                    'token' => $token ? [
                        'id' => $token->id,
                        'label' => $this->resolveTokenLabel($token->name, (bool) $token->hidden),
                        'visibility' => $token->hidden ? 'obscured' : 'visible',
                        'faction' => $token->faction,
                    ] : null,
                    'condition' => [
                        'key' => $adjustment->condition_key,
                        'label' => Str::title(str_replace('_', ' ', $adjustment->condition_key)),
                        'previous_rounds' => $adjustment->previous_rounds,
                        'new_rounds' => $adjustment->new_rounds,
                        'delta' => $adjustment->delta,
                        'urgency' => $urgency,
                        'summary' => ConditionSummaryCopy::for(
                            $adjustment->condition_key,
                            $urgency,
                            [
                                ':target' => $this->resolveTokenLabel(
                                    $token?->name,
                                    (bool) ($token?->hidden ?? false)
                                ),
                            ]
                        ),
                    ],
                    'recorded_at' => optional($adjustment->recorded_at)->toIso8601String(),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, int>  $campaignIds
     * @param  Collection<int, int>  $groupIds
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectQuestUpdates(Collection $campaignIds, Collection $groupIds, CarbonImmutable $since): Collection
    {
        if ($campaignIds->isEmpty() && $groupIds->isEmpty()) {
            return collect();
        }

        return CampaignQuestUpdate::query()
            ->where(function ($query) use ($campaignIds, $groupIds): void {
                $applied = false;

                if ($campaignIds->isNotEmpty()) {
                    $query->whereHas('quest', function ($questQuery) use ($campaignIds): void {
                        $questQuery->whereIn('campaign_id', $campaignIds);
                    });
                    $applied = true;
                }

                if ($groupIds->isNotEmpty()) {
                    $method = $applied ? 'orWhereHas' : 'whereHas';
                    $query->{$method}('quest.campaign', function ($campaignQuery) use ($groupIds): void {
                        $campaignQuery->whereIn('group_id', $groupIds);
                    });
                }
            })
            ->where(function ($query) use ($since): void {
                $query->where(function ($recorded) use ($since): void {
                    $recorded->whereNotNull('recorded_at')
                        ->where('recorded_at', '>=', $since);
                })->orWhere(function ($created) use ($since): void {
                    $created->whereNull('recorded_at')
                        ->where('created_at', '>=', $since);
                });
            })
            ->with([
                'quest:id,campaign_id,title,status,priority',
                'quest.campaign:id,title,group_id',
            ])
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (CampaignQuestUpdate $update): array {
                $quest = $update->quest;
                $campaign = $quest?->campaign;

                return [
                    'quest' => [
                        'id' => $quest?->id,
                        'title' => $quest?->title,
                        'status' => $quest?->status,
                        'priority' => $quest?->priority,
                    ],
                    'campaign' => $campaign ? [
                        'id' => $campaign->id,
                        'title' => $campaign->title,
                    ] : null,
                    'summary' => $update->summary,
                    'details' => $update->details,
                    'recorded_at' => optional($update->recorded_at ?? $update->created_at)->toIso8601String(),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, int>  $campaignIds
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectRewardUpdates(Collection $campaignIds, CarbonImmutable $since): Collection
    {
        if ($campaignIds->isEmpty()) {
            return collect();
        }

        return SessionReward::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('created_at', '>=', $since)
            ->with([
                'campaign:id,title',
                'session:id,campaign_id,title,session_date',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (SessionReward $reward): array {
                $campaign = $reward->campaign;
                $session = $reward->session;

                return [
                    'reward' => [
                        'id' => $reward->id,
                        'type' => $reward->reward_type,
                        'title' => $reward->title,
                        'quantity' => $reward->quantity,
                        'awarded_to' => $reward->awarded_to,
                        'notes' => $reward->notes,
                    ],
                    'campaign' => $campaign ? [
                        'id' => $campaign->id,
                        'title' => $campaign->title,
                    ] : null,
                    'session' => $session ? [
                        'id' => $session->id,
                        'title' => $session->title,
                        'session_date' => optional($session->session_date)->toDateString(),
                    ] : null,
                    'recorded_at' => optional($reward->created_at)->toIso8601String(),
                ];
            })
            ->values();
    }

    protected function resolveUrgency(?int $rounds): string
    {
        return ConditionTimerInsights::urgency($rounds);
    }

    protected function resolveTokenLabel(?string $name, bool $hidden): string
    {
        if (! $hidden && filled($name)) {
            return $name;
        }

        return 'Shrouded presence';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $conditions
     */
    protected function resolveDigestUrgency(Collection $conditions): string
    {
        if ($conditions->contains(fn (array $entry): bool => Arr::get($entry, 'condition.urgency') === 'critical')) {
            return 'critical';
        }

        if ($conditions->contains(fn (array $entry): bool => Arr::get($entry, 'condition.urgency') === 'warning')) {
            return 'warning';
        }

        return 'calm';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $conditions
     * @param  Collection<int, array<string, mixed>>  $quests
     * @param  Collection<int, array<string, mixed>>  $rewards
     */
    protected function renderMarkdown(
        User $user,
        CarbonImmutable $now,
        CarbonImmutable $since,
        Collection $conditions,
        Collection $quests,
        Collection $rewards,
        Collection $mentorCatchUps,
        string $urgency
    ): string {
        $lines = [];

        $lines[] = sprintf('# Player digest for %s', $user->name ?? 'your party');
        $lines[] = sprintf('_Window: %s → %s UTC_', $since->format('Y-m-d H:i'), $now->format('Y-m-d H:i'));
        $lines[] = sprintf('_Urgency: **%s**_', Str::title($urgency));
        $lines[] = '';

        if ($mentorCatchUps->isNotEmpty()) {
            $lines[] = '## '.trans('condition_timers.share_view.catch_up.title');
            $lines[] = trans('condition_timers.share_view.catch_up.email_intro');

            foreach ($mentorCatchUps as $entry) {
                $excerpt = Arr::get($entry, 'excerpt', '');
                $deliveredAt = Arr::get($entry, 'delivered_at');
                $formatted = $deliveredAt;

                if ($deliveredAt) {
                    try {
                        $formatted = CarbonImmutable::parse($deliveredAt, 'UTC')->format('Y-m-d H:i');
                    } catch (\Throwable) {
                        $formatted = $deliveredAt;
                    }
                }

                $lines[] = trans('condition_timers.share_view.catch_up.item_email', [
                    'excerpt' => $excerpt,
                    'timestamp' => $formatted ?? trans('condition_timers.generic.unknown'),
                ]);
            }

            $lines[] = trans('condition_timers.share_view.catch_up.cta');
            $lines[] = '';
        }

        if ($conditions->isNotEmpty()) {
            $lines[] = '## Condition highlights';

            foreach ($conditions as $entry) {
                $token = Arr::get($entry, 'token.label', 'Unknown token');
                $summary = Arr::get($entry, 'condition.summary');
                $rounds = Arr::get($entry, 'condition.new_rounds');
                $roundsLabel = $rounds === null ? '—' : $rounds;
                $lines[] = sprintf('- **%s** · %s (rounds remaining: %s)', $token, $summary, $roundsLabel);
            }

            $lines[] = '';
        }

        if ($quests->isNotEmpty()) {
            $lines[] = '## Quest updates';

            foreach ($quests as $entry) {
                $quest = Arr::get($entry, 'quest.title', 'Quest');
                $campaign = Arr::get($entry, 'campaign.title');
                $summary = Arr::get($entry, 'summary');
                $lines[] = sprintf('- **%s**%s — %s', $quest, $campaign ? sprintf(' (%s)', $campaign) : '', $summary);
            }

            $lines[] = '';
        }

        if ($rewards->isNotEmpty()) {
            $lines[] = '## Loot & rewards';

            foreach ($rewards as $entry) {
                $title = Arr::get($entry, 'reward.title', 'Reward');
                $recipient = Arr::get($entry, 'reward.awarded_to');
                $quantity = Arr::get($entry, 'reward.quantity');
                $session = Arr::get($entry, 'session.title');
                $pieces = array_filter([
                    $recipient ? sprintf('awarded to %s', $recipient) : null,
                    $quantity !== null ? sprintf('x%s', $quantity) : null,
                    $session ? sprintf('session: %s', $session) : null,
                ]);
                $trail = $pieces === [] ? '' : sprintf(' — %s', implode(', ', $pieces));
                $lines[] = sprintf('- **%s**%s', $title, $trail);
            }

            $lines[] = '';
        }

        if ($conditions->isEmpty() && $quests->isEmpty() && $rewards->isEmpty()) {
            $lines[] = '_No new updates in this window — rest easy and check back after the next adventure._';
        }

        return implode("\n", $lines);
    }
}
