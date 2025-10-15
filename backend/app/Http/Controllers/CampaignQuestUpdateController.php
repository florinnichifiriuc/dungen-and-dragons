<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignQuestProgressStoreRequest;
use App\Models\Campaign;
use App\Models\CampaignQuest;
use App\Models\CampaignQuestUpdate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;

class CampaignQuestUpdateController extends Controller
{
    public function store(CampaignQuestProgressStoreRequest $request, Campaign $campaign, CampaignQuest $quest): RedirectResponse
    {
        $this->assertQuestCampaign($campaign, $quest);
        $this->authorize('create', [CampaignQuestUpdate::class, $quest]);

        /** @var Authenticatable $user */
        $user = $request->user();

        $data = $request->validated();

        $quest->updates()->create([
            'summary' => $data['summary'],
            'details' => $data['details'] ?? null,
            'recorded_at' => $data['recorded_at'] ?? now(),
            'created_by_id' => $user->getAuthIdentifier(),
        ]);

        return redirect()
            ->route('campaigns.quests.show', [$campaign, $quest])
            ->with('success', 'Progress logged.');
    }

    public function destroy(Campaign $campaign, CampaignQuest $quest, CampaignQuestUpdate $update): RedirectResponse
    {
        $this->assertQuestCampaign($campaign, $quest);

        if ($update->quest_id !== $quest->id) {
            abort(404);
        }

        $this->authorize('delete', $update);

        $update->delete();

        return redirect()
            ->route('campaigns.quests.show', [$campaign, $quest])
            ->with('success', 'Progress update removed.');
    }

    protected function assertQuestCampaign(Campaign $campaign, CampaignQuest $quest): void
    {
        if ($quest->campaign_id !== $campaign->id) {
            abort(404);
        }
    }
}
