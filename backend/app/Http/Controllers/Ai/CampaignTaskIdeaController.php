<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AiIdeaRequest;
use App\Models\Campaign;
use App\Models\CampaignTask;
use App\Services\AiContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CampaignTaskIdeaController extends Controller
{
    public function __invoke(AiIdeaRequest $request, Campaign $campaign, AiContentService $ai): JsonResponse
    {
        Gate::authorize('create', [CampaignTask::class, $campaign]);

        $result = $ai->draftCampaignTasks($campaign, (string) $request->input('prompt', ''), $request->user());

        return response()->json($result);
    }
}
