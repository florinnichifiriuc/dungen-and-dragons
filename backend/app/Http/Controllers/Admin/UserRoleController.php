<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRoleUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserRoleController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manageUserRoles');

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'account_role', 'is_support_admin', 'created_at']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_role' => $user->account_role,
                'is_support_admin' => (bool) $user->is_support_admin,
                'created_at' => $user->created_at?->toIso8601String(),
            ]),
            'roles' => User::accountRoles(),
        ]);
    }

    public function update(UserRoleUpdateRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('manageUserRoles');

        $validated = $request->validated();

        $user->forceFill([
            'account_role' => $validated['account_role'],
        ])->save();

        if ($user->account_role !== 'admin' && $user->is_support_admin) {
            $user->forceFill(['is_support_admin' => false])->save();
        }

        if ($user->account_role === 'admin' && ! $user->is_support_admin) {
            $user->forceFill(['is_support_admin' => true])->save();
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', sprintf('Updated role for %s.', $user->name));
    }
}
