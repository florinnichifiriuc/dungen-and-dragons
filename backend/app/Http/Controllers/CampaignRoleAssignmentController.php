<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignRoleAssignmentStoreRequest;
use App\Models\Campaign;
use App\Models\CampaignRoleAssignment;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class CampaignRoleAssignmentController extends Controller
{
    public function store(CampaignRoleAssignmentStoreRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        /** @var Authenticatable&User $actingUser */
        $actingUser = $request->user();

        $assigneeTypeInput = $request->string('assignee_type')->toString();
        $assigneeClass = $assigneeTypeInput === 'group' ? Group::class : ($assigneeTypeInput === 'user' ? User::class : $assigneeTypeInput);

        if ($assigneeClass !== Group::class && $assigneeClass !== User::class) {
            throw ValidationException::withMessages([
                'assignee_type' => 'Unsupported assignee type.',
            ]);
        }

        $assignee = $assigneeClass::find($request->integer('assignee_id'));

        if (! $assignee) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Assignee could not be found.',
            ]);
        }

        if ($assignee instanceof Group) {
            $this->ensureGroupAssignable($campaign, $actingUser, $assignee);
        }

        if ($assignee instanceof User) {
            $belongsToGroup = $assignee->groups()->where('groups.id', $campaign->group_id)->exists();

            if (! $belongsToGroup) {
                throw ValidationException::withMessages([
                    'assignee_id' => 'Assignee must belong to the campaign group.',
                ]);
            }
        }

        $alreadyAssigned = $campaign->roleAssignments()
            ->where('assignee_type', $assigneeClass)
            ->where('assignee_id', $assignee->getKey())
            ->where('role', $request->string('role')->toString())
            ->exists();

        if ($alreadyAssigned) {
            throw ValidationException::withMessages([
                'role' => 'Assignee already has this role.',
            ]);
        }

        $campaign->roleAssignments()->create([
            'assignee_type' => $assigneeClass,
            'assignee_id' => $assignee->getKey(),
            'role' => $request->string('role')->toString(),
            'scope' => $request->string('scope')->isNotEmpty() ? $request->string('scope')->toString() : 'campaign',
            'status' => $request->string('status')->isNotEmpty() ? $request->string('status')->toString() : CampaignRoleAssignment::STATUS_ACTIVE,
            'assigned_by' => $actingUser->getAuthIdentifier(),
            'accepted_at' => $assignee instanceof User && $request->boolean('accept_immediately', true) ? now() : null,
        ]);

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Role assigned.');
    }

    public function destroy(Campaign $campaign, CampaignRoleAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $campaign);

        if ($assignment->campaign_id !== $campaign->id) {
            abort(404);
        }

        $assignment->delete();

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Role removed.');
    }

    protected function ensureGroupAssignable(Campaign $campaign, User $actingUser, Group $group): void
    {
        if ($group->id !== $campaign->group_id) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Only the owning group can be assigned at this stage.',
            ]);
        }

        $this->ensureGroupManager($actingUser, $group);
    }

    protected function ensureGroupManager(User $user, Group $group): void
    {
        $manager = $group->memberships()
            ->where('user_id', $user->id)
            ->whereIn('role', [
                GroupMembership::ROLE_OWNER,
                GroupMembership::ROLE_DUNGEON_MASTER,
            ])
            ->exists();

        if (! $manager) {
            throw ValidationException::withMessages([
                'assignee_type' => 'You cannot assign roles to this group.',
            ]);
        }
    }
}
