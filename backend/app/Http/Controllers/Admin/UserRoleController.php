<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRoleUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserRoleController extends Controller
{
    public function index(): Response
    {
        $this->authorizeSupportAdmin();

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_support_admin', 'created_at']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_support_admin' => (bool) $user->is_support_admin,
                'created_at' => $user->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function update(UserRoleUpdateRequest $request, User $user): RedirectResponse
    {
        $this->authorizeSupportAdmin();

        $validated = $request->validated();

        $user->forceFill([
            'is_support_admin' => $validated['is_support_admin'],
        ])->save();

        return redirect()
            ->back()
            ->with('success', sprintf('Updated support access for %s.', $user->name));
    }

    protected function authorizeSupportAdmin(): void
    {
        abort_unless($requestUser = request()->user(), 403);
        abort_unless($requestUser->is_support_admin, 403);
    }
}
