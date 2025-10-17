<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConditionTimerShareConsentRequest;
use App\Models\Group;
use App\Models\User;
use App\Services\ConditionTimerShareConsentService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;

class ConditionTimerShareConsentController extends Controller
{
    public function __construct(private readonly ConditionTimerShareConsentService $consents)
    {
    }

    public function store(ConditionTimerShareConsentRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        /** @var Authenticatable $actor */
        $actor = $request->user();
        $subject = User::query()->findOrFail($request->integer('user_id'));
        $consented = $request->boolean('consented');
        $visibility = $request->input('visibility_mode', 'counts');
        $notes = $request->input('notes');

        $this->consents->recordConsent($group, $subject, $actor, $consented, $visibility, 'facilitator', $notes);

        return redirect()->back()->with('success', 'Consent updated.');
    }
}
