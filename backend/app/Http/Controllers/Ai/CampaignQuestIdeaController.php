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
        Gate::authorize('create', [CampaignQuest::class, $campaign]);

        $result = $ai->draftQuest($campaign, (string) $request->input('prompt', ''), $request->user());

        return response()->json($result);
    }
}
