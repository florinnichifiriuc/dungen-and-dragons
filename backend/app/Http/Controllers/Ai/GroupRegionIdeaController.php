<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AiIdeaRequest;
use App\Models\Group;
use App\Models\Region;
use App\Services\AiContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class GroupRegionIdeaController extends Controller
{
    public function __invoke(AiIdeaRequest $request, Group $group, AiContentService $ai): JsonResponse
    {
        Gate::authorize('create', [Region::class, $group]);

        $result = $ai->draftRegion($group, (string) $request->input('prompt', ''), $request->user());

        return response()->json([
            'idea' => $result['summary'],
            'structured' => [
                'summary' => $result['summary'],
                'description' => $result['fields']['description'] ?? null,
                'fields' => $result['fields'],
                'tips' => $result['tips'],
                'image_prompt' => $result['image_prompt'],
            ],
        ]);
    }
}
