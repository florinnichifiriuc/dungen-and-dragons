<?php

namespace App\Http\Controllers;

use App\Http\Requests\SessionAttendanceRequest;
use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\SessionAttendance;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SessionAttendanceController extends Controller
{
    public function store(SessionAttendanceRequest $request, Campaign $campaign, CampaignSession $session): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('respond', $session);

        /** @var Authenticatable&User $user */
        $user = $request->user();

        $data = $request->validated();

        $session->attendances()->updateOrCreate(
            [
                'user_id' => $user->getAuthIdentifier(),
            ],
            [
                'status' => $data['status'],
                'note' => $data['note'] ?? null,
                'responded_at' => now('UTC'),
            ]
        );

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'RSVP saved.');
    }

    public function destroy(Request $request, Campaign $campaign, CampaignSession $session): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('respond', $session);

        /** @var Authenticatable&User $user */
        $user = $request->user();

        SessionAttendance::query()
            ->where('campaign_session_id', $session->id)
            ->where('user_id', $user->getAuthIdentifier())
            ->delete();

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'RSVP cleared.');
    }

    protected function ensureSessionBelongsToCampaign(Campaign $campaign, CampaignSession $session): void
    {
        if ($session->campaign_id !== $campaign->id) {
            abort(404);
        }
    }
}
