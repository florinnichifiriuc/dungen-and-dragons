<?php

namespace App\Http\Controllers;

use App\Http\Requests\SessionNoteStoreRequest;
use App\Http\Requests\SessionNoteUpdateRequest;
use App\Models\Campaign;
use App\Models\CampaignRoleAssignment;
use App\Models\GroupMembership;
use App\Models\CampaignSession;
use App\Models\SessionNote;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class SessionNoteController extends Controller
{
    public function store(SessionNoteStoreRequest $request, Campaign $campaign, CampaignSession $session): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('create', [SessionNote::class, $session]);

        /** @var Authenticatable&User $user */
        $user = $request->user();
        $isManager = $this->userManagesCampaign($user, $campaign);

        $visibility = $request->string('visibility')->toString();
        $isPinned = $request->boolean('is_pinned');

        if (! $isManager) {
            if ($visibility === SessionNote::VISIBILITY_GM) {
                throw ValidationException::withMessages([
                    'visibility' => 'Only game masters can create GM-only notes.',
                ]);
            }

            $isPinned = false;
        }

        $session->notes()->create([
            'campaign_id' => $campaign->id,
            'author_id' => $user->getAuthIdentifier(),
            'visibility' => $visibility,
            'is_pinned' => $isPinned,
            'content' => $request->string('content')->toString(),
        ]);

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Note added to the chronicle.');
    }

    public function update(SessionNoteUpdateRequest $request, Campaign $campaign, CampaignSession $session, SessionNote $note): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->ensureNoteBelongsToSession($session, $note);
        $this->authorize('update', $note);

        /** @var Authenticatable&User $user */
        $user = $request->user();
        $isManager = $this->userManagesCampaign($user, $campaign);

        $payload = $request->validated();

        if (! $isManager) {
            if (isset($payload['visibility']) && $payload['visibility'] === SessionNote::VISIBILITY_GM) {
                unset($payload['visibility']);
            }

            if (isset($payload['is_pinned'])) {
                $payload['is_pinned'] = false;
            }
        }

        $note->update($payload);

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Note updated.');
    }

    public function destroy(Campaign $campaign, CampaignSession $session, SessionNote $note): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->ensureNoteBelongsToSession($session, $note);
        $this->authorize('delete', $note);

        $note->delete();

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Note removed.');
    }

    protected function ensureSessionBelongsToCampaign(Campaign $campaign, CampaignSession $session): void
    {
        if ($session->campaign_id !== $campaign->id) {
            abort(404);
        }
    }

    protected function ensureNoteBelongsToSession(CampaignSession $session, SessionNote $note): void
    {
        if ($note->campaign_session_id !== $session->id) {
            abort(404);
        }
    }

    protected function userManagesCampaign(User $user, Campaign $campaign): bool
    {
        if ($campaign->created_by === $user->id) {
            return true;
        }

        $isManager = $campaign->group->memberships()
            ->where('user_id', $user->id)
            ->whereIn('role', [
                GroupMembership::ROLE_OWNER,
                GroupMembership::ROLE_DUNGEON_MASTER,
            ])
            ->exists();

        if ($isManager) {
            return true;
        }

        return $campaign->roleAssignments()
            ->where('assignee_type', User::class)
            ->where('assignee_id', $user->id)
            ->where('role', CampaignRoleAssignment::ROLE_GM)
            ->exists();
    }
}
