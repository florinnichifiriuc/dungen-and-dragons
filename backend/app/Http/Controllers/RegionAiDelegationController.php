<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegionAiDelegateRequest;
use App\Models\Group;
use App\Models\Region;
use App\Services\AiContentService;
use Illuminate\Http\RedirectResponse;

class RegionAiDelegationController extends Controller
{
    public function store(RegionAiDelegateRequest $request, Group $group, Region $region, AiContentService $ai): RedirectResponse
    {
        $this->assertRegionForGroup($group, $region);

        $result = $ai->delegateRegionToAi(
            $region,
            $request->user(),
            $request->validated()['focus'] ?? null
        );

        return redirect()
            ->route('groups.show', $group)
            ->with('success', 'AI delegate assigned to the region.')
            ->with('ai_delegate_plan', $result['plan']);
    }

    protected function assertRegionForGroup(Group $group, Region $region): void
    {
        abort_if($region->group_id !== $group->id, 404);
    }
}
