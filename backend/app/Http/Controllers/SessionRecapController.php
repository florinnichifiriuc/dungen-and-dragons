<?php

namespace App\Http\Controllers;

use App\Http\Requests\SessionRecapRequest;
use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\SessionRecap;
use App\Models\User;
use App\Policies\SessionPolicy;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SessionRecapController extends Controller
{
    public function store(SessionRecapRequest $request, Campaign $campaign, CampaignSession $session): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('recap', $session);

        /** @var Authenticatable&User $user */
        $user = $request->user();

        $data = $request->validated();

        $session->recaps()->create([
            'campaign_id' => $campaign->id,
            'author_id' => $user->getAuthIdentifier(),
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
        ]);

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Recap shared.');
    }

    public function destroy(Request $request, Campaign $campaign, CampaignSession $session, SessionRecap $recap): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);

        if ($recap->campaign_session_id !== $session->id || $recap->campaign_id !== $campaign->id) {
            abort(404);
        }

        /** @var Authenticatable&User $user */
        $user = $request->user();

        $sessionPolicy = app(SessionPolicy::class);

        $canModerate = $sessionPolicy->update($user, $session);
        $isAuthor = $recap->author_id === $user->getAuthIdentifier();

        if (! $canModerate && ! $isAuthor) {
            abort(403);
        }

        $recap->delete();

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Recap removed.');
    }

    protected function ensureSessionBelongsToCampaign(Campaign $campaign, CampaignSession $session): void
    {
        if ($session->campaign_id !== $campaign->id) {
            abort(404);
        }
    }
}
