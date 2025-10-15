<?php

namespace App\Http\Controllers;

use App\Models\CampaignInvitation;
use App\Models\CampaignRoleAssignment;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CampaignInvitationAcceptController extends Controller
{
    public function show(Request $request, CampaignInvitation $invitation): Response|RedirectResponse
    {
        $invitation->loadMissing('campaign.group', 'group');

        $this->authorize('respond', $invitation);

        if ($invitation->accepted_at !== null) {
            return redirect()
                ->route('campaigns.show', $invitation->campaign)
                ->with('error', 'This invitation has already been accepted.');
        }

        if ($invitation->expires_at !== null && $invitation->expires_at->isPast()) {
            return redirect()
                ->route('campaigns.show', $invitation->campaign)
                ->with('error', 'This invitation has expired.');
        }

        return Inertia::render('CampaignInvitations/Accept', [
            'campaign' => [
                'id' => $invitation->campaign->id,
                'title' => $invitation->campaign->title,
                'group' => [
                    'id' => $invitation->campaign->group->id,
                    'name' => $invitation->campaign->group->name,
                ],
            ],
            'invitation' => [
                'id' => $invitation->id,
                'role' => $invitation->role,
                'email' => $invitation->email,
                'group' => $invitation->group ? [
                    'id' => $invitation->group->id,
                    'name' => $invitation->group->name,
                ] : null,
                'expires_at' => optional($invitation->expires_at)->toAtomString(),
                'accept_route' => route('campaigns.invitations.accept.store', ['invitation' => $invitation->token]),
            ],
        ]);
    }

    public function store(Request $request, CampaignInvitation $invitation): RedirectResponse
    {
        $invitation->loadMissing('campaign.group', 'group');

        $this->authorize('respond', $invitation);

        if ($invitation->accepted_at !== null) {
            return redirect()
                ->route('campaigns.show', $invitation->campaign)
                ->with('error', 'This invitation has already been accepted.');
        }

        if ($invitation->expires_at !== null && $invitation->expires_at->isPast()) {
            return redirect()
                ->route('campaigns.show', $invitation->campaign)
                ->with('error', 'This invitation has expired.');
        }

        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($invitation, $user): void {
            $assignmentAttributes = [
                'role' => $invitation->role,
            ];

            if ($invitation->group !== null) {
                $assignmentAttributes['assignee_type'] = Group::class;
                $assignmentAttributes['assignee_id'] = $invitation->group->id;
            } else {
                $assignmentAttributes['assignee_type'] = User::class;
                $assignmentAttributes['assignee_id'] = $user->id;

                $campaignGroup = $invitation->campaign->group;

                GroupMembership::firstOrCreate(
                    [
                        'group_id' => $campaignGroup->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'role' => GroupMembership::ROLE_PLAYER,
                    ],
                );
            }

            $assignment = $invitation->campaign->roleAssignments()
                ->firstOrNew($assignmentAttributes);

            $assignment->status = CampaignRoleAssignment::STATUS_ACTIVE;
            $assignment->accepted_at = now();

            if (! $assignment->exists) {
                $assignment->assigned_by = $invitation->invited_by ?? $user->id;
            }

            $assignment->save();

            $invitation->forceFill([
                'accepted_at' => now(),
            ])->save();
        });

        return redirect()
            ->route('campaigns.show', $invitation->campaign)
            ->with('success', 'Invitation accepted. Welcome to the campaign.');
    }
}
