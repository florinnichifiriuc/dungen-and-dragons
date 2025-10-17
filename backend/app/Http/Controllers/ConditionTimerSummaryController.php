<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Services\ConditionMentorBriefingService;
use App\Services\ConditionTimerAcknowledgementService;
use App\Services\ConditionTimerChronicleService;
use App\Services\ConditionTimerShareConsentService;
use App\Services\ConditionTimerSummaryProjector;
use App\Services\ConditionTimerSummaryShareService;
use Illuminate\Contracts\Auth\Authenticatable;
use Inertia\Inertia;
use Inertia\Response;

class ConditionTimerSummaryController extends Controller
{
    public function __construct(
        private readonly ConditionTimerSummaryProjector $projector,
        private readonly ConditionTimerAcknowledgementService $acknowledgements,
        private readonly ConditionTimerChronicleService $chronicle,
        private readonly ConditionTimerSummaryShareService $shareService,
        private readonly ConditionTimerShareConsentService $consents,
        private readonly ConditionMentorBriefingService $mentorBriefings
    )
    {
    }

    public function show(Group $group): Response
    {
        $this->authorize('view', $group);

        /** @var Authenticatable $user */
        $user = auth()->user();

        $summary = $this->projector->projectForGroup($group);

        $viewerRole = $group->memberships()
            ->where('user_id', auth()->id())
            ->value('role');

        $canViewAggregate = in_array(
            $viewerRole,
            [GroupMembership::ROLE_OWNER, GroupMembership::ROLE_DUNGEON_MASTER],
            true,
        );

        $summary = $this->acknowledgements->hydrateSummaryForUser(
            $summary,
            $group,
            $user,
            $canViewAggregate,
        );

        $summary = $this->chronicle->hydrateSummaryForUser(
            $summary,
            $group,
            $user,
            $canViewAggregate,
        );

        $activeShare = $this->shareService->activeShareForGroup($group);

        $share = $activeShare ? [
            'id' => $activeShare->id,
            'url' => route('shares.condition-timers.player-summary.show', $activeShare->token),
            'created_at' => $activeShare->created_at?->toIso8601String(),
            'expires_at' => $activeShare->expires_at?->toIso8601String(),
            'visibility_mode' => $activeShare->visibility_mode,
            'access_count' => $activeShare->access_count,
            'last_accessed_at' => $activeShare->last_accessed_at?->toIso8601String(),
            'state' => $this->shareService->describeShareState($activeShare),
            'access_trend' => $this->shareService->accessTrend($activeShare),
            'extend_route' => route(
                'groups.condition-timers.player-summary.share-links.extend',
                [$group->id, $activeShare->id]
            ),
        ] : null;

        $consentStatuses = $this->consents->currentStatuses($group)->values()->all();
        $consentAudit = $this->consents->auditTrail($group, 12)->values()->all();

        $recentExports = $group->conditionTransparencyExports()
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($export) => [
                'id' => $export->id,
                'format' => $export->format,
                'visibility_mode' => $export->visibility_mode,
                'status' => $export->status,
                'completed_at' => $export->completed_at?->toIso8601String(),
                'download_url' => $export->status === 'completed'
                    ? route('groups.condition-transparency.exports.download', [$group->id, $export->id])
                    : null,
            ])
            ->all();

        $webhooks = $group->conditionTransparencyWebhooks()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($webhook) => [
                'id' => $webhook->id,
                'url' => $webhook->url,
                'active' => (bool) $webhook->active,
                'call_count' => $webhook->call_count,
                'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
            ])
            ->all();

        $mentorBriefing = $this->mentorBriefings->getBriefing($group);

        return Inertia::render('Groups/ConditionTimerSummary', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'viewer_role' => $viewerRole,
                'mentor_briefings_enabled' => (bool) $group->mentor_briefings_enabled,
            ],
            'summary' => $summary,
            'share' => $share,
            'can_manage_share' => $canViewAggregate,
            'share_settings' => [
                'expiry_presets' => [
                    ['key' => '24h', 'label' => '24 hours'],
                    ['key' => '72h', 'label' => '72 hours'],
                    ['key' => 'custom', 'label' => 'Custom'],
                    ['key' => 'never', 'label' => 'Never'],
                ],
                'visibility_modes' => [
                    ['key' => 'counts', 'label' => 'Anonymized counts'],
                    ['key' => 'details', 'label' => 'Full details'],
                ],
                'consents' => $consentStatuses,
                'audit_log' => $consentAudit,
                'consent_route' => route('groups.condition-timers.share-consents.store', $group->id),
                'extend_presets' => [
                    ['key' => '24h', 'label' => 'Add 24 hours'],
                    ['key' => '72h', 'label' => 'Add 72 hours'],
                    ['key' => 'custom', 'label' => 'Custom extension'],
                    ['key' => 'never', 'label' => 'Convert to evergreen'],
                ],
            ],
            'export_settings' => [
                'request_route' => route('groups.condition-transparency.exports.store', $group->id),
                'formats' => ['csv', 'json'],
                'visibility_modes' => ['counts', 'details'],
                'recent_exports' => $recentExports,
                'webhooks' => $webhooks,
                'webhook_route' => route('groups.condition-transparency.webhooks.store', $group->id),
            ],
            'mentor_briefing' => $mentorBriefing,
        ]);
    }
}
