<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NpcDialogueRequest;
use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Services\AiContentService;
use Illuminate\Http\JsonResponse;

class SessionNpcDialogueController extends Controller
{
    public function __invoke(NpcDialogueRequest $request, Campaign $campaign, CampaignSession $session, AiContentService $ai): JsonResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);

        $result = $ai->npcDialogue(
            $session,
            $request->string('npc_name')->toString(),
            $request->string('prompt')->toString(),
            $request->user(),
            $request->input('tone')
        );

        return response()->json([
            'request_id' => $result['request']->id,
            'status' => $result['request']->status,
            'reply' => $result['reply'],
            'created_at' => optional($result['request']->created_at)->toIso8601String(),
        ]);
    }

    protected function ensureSessionBelongsToCampaign(Campaign $campaign, CampaignSession $session): void
    {
        abort_if($session->campaign_id !== $campaign->id, 404);
    }
}
