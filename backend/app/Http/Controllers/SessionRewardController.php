<?php

namespace App\Http\Controllers;

use App\Http\Requests\SessionRewardRequest;
use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\SessionReward;
use App\Models\User;
use App\Policies\SessionPolicy;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SessionRewardController extends Controller
{
    public function store(SessionRewardRequest $request, Campaign $campaign, CampaignSession $session): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('reward', $session);

        /** @var Authenticatable&User $user */
        $user = $request->user();

        $data = $request->validated();

        $session->rewards()->create([
            'campaign_id' => $campaign->id,
            'recorded_by' => $user->getAuthIdentifier(),
            'reward_type' => $data['reward_type'],
            'title' => $data['title'],
            'quantity' => $data['quantity'] ?? null,
            'awarded_to' => $data['awarded_to'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Reward logged for this session.');
    }

    public function destroy(Request $request, Campaign $campaign, CampaignSession $session, SessionReward $reward): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);

        if ((string) $reward->campaign_session_id !== (string) $session->id
            || (string) $reward->campaign_id !== (string) $campaign->id) {
            abort(404);
        }

        /** @var Authenticatable&User $user */
        $user = $request->user();

        $sessionPolicy = app(SessionPolicy::class);

        $canModerate = $sessionPolicy->update($user, $session);
        $isAuthor = $reward->recorded_by === $user->getAuthIdentifier();

        if (! $canModerate && ! $isAuthor) {
            abort(403);
        }

        $reward->delete();

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Reward entry removed.');
    }

    protected function ensureSessionBelongsToCampaign(Campaign $campaign, CampaignSession $session): void
    {
        if ($session->campaign_id !== $campaign->id) {
            abort(404);
        }
    }
}
