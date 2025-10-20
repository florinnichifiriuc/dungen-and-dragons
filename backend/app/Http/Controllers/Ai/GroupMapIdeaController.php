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
        $this->authorize('update', $map);
        Gate::authorize('view', $group);

        $result = $ai->draftMapPlan($map, (string) $request->input('prompt', ''), $request->user());

        return response()->json([
            'idea' => $result['summary'],
            'structured' => [
                'summary' => $result['summary'],
                'description' => null,
                'fields' => $result['fields'],
                'tips' => $result['tips'],
                'image_prompt' => $result['image_prompt'],
            ],
        ]);
    }
}
