<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroupMembershipStoreRequest;
use App\Http\Requests\GroupMembershipUpdateRequest;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GroupMembershipController extends Controller
{
    public function store(GroupMembershipStoreRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('create', [GroupMembership::class, $group]);

        $email = $request->string('email')->toString();
        $role = $request->string('role')->toString();

        $user = User::where('email', $email)->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => 'No adventurer with that email could be found.',
            ]);
        }

        if ($group->memberships()->where('user_id', $user->getKey())->exists()) {
            throw ValidationException::withMessages([
                'email' => 'That hero already travels with this party.',
            ]);
        }

        /** @var Authenticatable&\App\Models\User $actingUser */
        $actingUser = $request->user();
        $actingMembership = $this->membershipFor($group, $actingUser);

        if ($role === GroupMembership::ROLE_OWNER && ! $this->isOwner($actingMembership)) {
            abort(403, 'Only an existing owner can grant the Game Master mantle.');
        }

        GroupMembership::create([
            'group_id' => $group->id,
            'user_id' => $user->getKey(),
            'role' => $role,
        ]);

        return redirect()->route('groups.show', $group)->with('success', 'Adventurer added to the roster.');
    }

    public function update(GroupMembershipUpdateRequest $request, Group $group, GroupMembership $membership): RedirectResponse
    {
        if ($membership->group_id !== $group->id) {
            abort(404);
        }

        $this->authorize('update', $membership);

        $newRole = $request->string('role')->toString();

        /** @var Authenticatable&\App\Models\User $actingUser */
        $actingUser = $request->user();
        $actingMembership = $this->membershipFor($group, $actingUser);

        if ($newRole === GroupMembership::ROLE_OWNER && ! $this->isOwner($actingMembership)) {
            abort(403, 'Only an existing owner can grant the Game Master mantle.');
        }

        if ($membership->role === GroupMembership::ROLE_OWNER
            && $newRole !== GroupMembership::ROLE_OWNER
            && $this->isLastOwner($group, $membership->id)) {
            throw ValidationException::withMessages([
                'role' => 'A party must keep at least one Game Master.',
            ]);
        }

        $membership->update([
            'role' => $newRole,
        ]);

        return redirect()->route('groups.show', $group)->with('success', 'Role updated.');
    }

    public function destroy(Request $request, Group $group, GroupMembership $membership): RedirectResponse
    {
        if ($membership->group_id !== $group->id) {
            abort(404);
        }

        $this->authorize('delete', $membership);

        /** @var Authenticatable&\App\Models\User $actingUser */
        $actingUser = $request->user();
        $actingMembership = $this->membershipFor($group, $actingUser);

        if ($membership->role === GroupMembership::ROLE_OWNER && ($actingMembership === null || ! $this->isOwner($actingMembership)) && $actingUser?->getAuthIdentifier() !== $membership->user_id) {
            abort(403, 'Only an owner can remove another owner.');
        }

        if ($membership->role === GroupMembership::ROLE_OWNER && $this->isLastOwner($group, $membership->id)) {
            throw ValidationException::withMessages([
                'membership' => 'At least one Game Master must remain to steward the realm.',
            ]);
        }

        $membership->delete();

        if ($actingUser?->getAuthIdentifier() === $membership->user_id) {
            return redirect()->route('groups.index')->with('success', 'You slip away from the party.');
        }

        return redirect()->route('groups.show', $group)->with('success', 'Member removed from the roster.');
    }

    protected function membershipFor(Group $group, ?Authenticatable $user): ?GroupMembership
    {
        if ($user === null) {
            return null;
        }

        return $group->memberships()->where('user_id', $user->getAuthIdentifier())->first();
    }

    protected function isOwner(?GroupMembership $membership): bool
    {
        return $membership !== null && $membership->role === GroupMembership::ROLE_OWNER;
    }

    protected function isLastOwner(Group $group, int $ignoreMembershipId): bool
    {
        return $group->memberships()
            ->where('id', '!=', $ignoreMembershipId)
            ->where('role', GroupMembership::ROLE_OWNER)
            ->count() === 0;
    }
}
