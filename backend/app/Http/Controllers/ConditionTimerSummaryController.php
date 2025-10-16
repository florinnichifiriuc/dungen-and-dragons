<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\ConditionTimerSummaryProjector;
use Inertia\Inertia;
use Inertia\Response;

class ConditionTimerSummaryController extends Controller
{
    public function __construct(private readonly ConditionTimerSummaryProjector $projector)
    {
    }

    public function show(Group $group): Response
    {
        $this->authorize('view', $group);

        $summary = $this->projector->projectForGroup($group);

        $viewerRole = $group->memberships()
            ->where('user_id', auth()->id())
            ->value('role');

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
