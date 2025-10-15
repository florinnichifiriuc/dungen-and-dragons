<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroupJoinRequest;
use App\Models\Group;
use App\Models\GroupMembership;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

class GroupJoinController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Groups/Join');
    }

    public function store(GroupJoinRequest $request): RedirectResponse
    {
        $code = strtoupper($request->string('code')->toString());

        $group = Group::where('join_code', $code)->first();

        if ($group === null) {
            throw ValidationException::withMessages([
                'code' => 'No party could be found with that join code.',
            ]);
        }

        /** @var Authenticatable&\App\Models\User $user */
        $user = $request->user();

        if ($group->memberships()->where('user_id', $user->getAuthIdentifier())->exists()) {
            return redirect()->route('groups.show', $group)->with('info', 'You already travel with this party.');
        }

        DB::transaction(function () use ($group, $user): void {
            GroupMembership::create([
                'group_id' => $group->id,
                'user_id' => $user->getAuthIdentifier(),
                'role' => GroupMembership::ROLE_PLAYER,
            ]);
        });

        return redirect()->route('groups.show', $group)->with('success', 'You join the party as a fresh adventurer.');
    }
}
