<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\ConditionMentorBriefingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GroupMentorBriefingPreferenceController extends Controller
{
    public function __construct(private readonly ConditionMentorBriefingService $mentorBriefings)
    {
    }

    public function update(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        $enabled = $request->boolean('enabled', true);
        $this->mentorBriefings->setEnabled($group, $enabled);

        return redirect()->back()->with('success', $enabled ? 'Mentor briefings enabled.' : 'Mentor briefings paused.');
    }
}
