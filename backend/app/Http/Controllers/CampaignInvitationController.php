<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignInvitationStoreRequest;
use App\Models\Campaign;
use App\Models\CampaignInvitation;
use App\Models\Group;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class CampaignInvitationController extends Controller
{
    public function store(CampaignInvitationStoreRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        /** @var Authenticatable $user */
        $user = $request->user();

        $groupId = $request->integer('group_id');
        if ($groupId !== 0 && $groupId !== null) {
            $group = Group::find($groupId);

            if ($group === null) {
                throw ValidationException::withMessages([
                    'group_id' => 'Selected group could not be found.',
                ]);
            }
        }

        $campaign->invitations()->create([
            'group_id' => $groupId ?: null,
            'email' => $request->input('email'),
            'role' => $request->string('role')->toString(),
            'token' => Str::random(40),
            'expires_at' => $request->date('expires_at'),
            'invited_by' => $user?->getAuthIdentifier(),
        ]);

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Invitation recorded.');
    }

    public function destroy(Campaign $campaign, CampaignInvitation $invitation): RedirectResponse
    {
        $this->authorize('update', $campaign);

        if ($invitation->campaign_id !== $campaign->id) {
            abort(404);
        }

        $invitation->delete();

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Invitation revoked.');
    }
}
