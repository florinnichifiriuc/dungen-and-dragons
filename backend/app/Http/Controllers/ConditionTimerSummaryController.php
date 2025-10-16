<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Services\ConditionTimerAcknowledgementService;
use App\Services\ConditionTimerChronicleService;
use App\Services\ConditionTimerSummaryProjector;
use Illuminate\Contracts\Auth\Authenticatable;
use Inertia\Inertia;
use Inertia\Response;

class ConditionTimerSummaryController extends Controller
{
    public function __construct(
        private readonly ConditionTimerSummaryProjector $projector,
        private readonly ConditionTimerAcknowledgementService $acknowledgements,
        private readonly ConditionTimerChronicleService $chronicle
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

        return Inertia::render('Groups/ConditionTimerSummary', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'viewer_role' => $viewerRole,
            ],
            'summary' => $summary,
        ]);
    }
}
