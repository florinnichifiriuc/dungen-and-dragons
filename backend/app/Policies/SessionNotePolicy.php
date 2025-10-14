<?php

namespace App\Policies;

use App\Models\CampaignSession;
use App\Models\SessionNote;
use App\Models\User;

class SessionNotePolicy
{
    public function view(User $user, SessionNote $note): bool
    {
        return app(CampaignPolicy::class)->view($user, $note->campaign);
    }

    public function create(User $user, CampaignSession $session): bool
    {
        return app(CampaignPolicy::class)->view($user, $session->campaign);
    }

    public function update(User $user, SessionNote $note): bool
    {
        if ($note->author_id === $user->id) {
            return true;
        }

        return app(CampaignPolicy::class)->update($user, $note->campaign);
    }

    public function delete(User $user, SessionNote $note): bool
    {
        if ($note->author_id === $user->id) {
            return true;
        }

        return app(CampaignPolicy::class)->update($user, $note->campaign);
    }
}
