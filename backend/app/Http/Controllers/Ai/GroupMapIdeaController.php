<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AiIdeaRequest;
use App\Models\Group;
use App\Models\Map;
use App\Services\AiContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class GroupMapIdeaController extends Controller
{
    public function __invoke(AiIdeaRequest $request, Group $group, Map $map, AiContentService $ai): JsonResponse
    {
        $this->assertMapGroup($group, $map);
        Gate::authorize('update', $map);

        $result = $ai->draftMapPlan($map, (string) $request->input('prompt', ''), $request->user());

        return response()->json($result);
    }

    protected function assertMapGroup(Group $group, Map $map): void
    {
        abort_unless($map->group_id === $group->id, 404);
    }
}
