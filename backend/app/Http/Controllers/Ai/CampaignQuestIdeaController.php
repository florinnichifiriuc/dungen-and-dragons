<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AiIdeaRequest;
use App\Models\Campaign;
use App\Models\CampaignQuest;
use App\Services\AiContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CampaignQuestIdeaController extends Controller
{
    public function __invoke(AiIdeaRequest $request, Campaign $campaign, AiContentService $ai): JsonResponse
    {
        Gate::authorize('update', $campaign);

        $result = $ai->draftQuest($campaign, (string) $request->input('prompt', ''), $request->user());

        return response()->json([
            'idea' => $result['summary'],
            'structured' => [
                'summary' => $result['summary'],
                'fields' => $result['fields'],
                'tips' => $result['tips'],
                'image_prompt' => $result['image_prompt'],
            ],
        ]);
    }
}
