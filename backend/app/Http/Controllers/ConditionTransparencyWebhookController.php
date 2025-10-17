<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConditionTransparencyWebhookRequest;
use App\Models\ConditionTransparencyWebhook;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;

class ConditionTransparencyWebhookController extends Controller
{
    public function store(ConditionTransparencyWebhookRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        $group->conditionTransparencyWebhooks()->create([
            'url' => $request->input('url'),
            'secret' => bin2hex(random_bytes(16)),
            'active' => true,
        ]);

        return redirect()->back()->with('success', 'Webhook added.');
    }

    public function rotate(Group $group, ConditionTransparencyWebhook $webhook): RedirectResponse
    {
        $this->authorize('update', $group);

        if ($webhook->group_id !== $group->id) {
            abort(404);
        }

        $webhook->rotateSecret();

        return redirect()->back()->with('success', 'Webhook secret rotated.');
    }

    public function destroy(Group $group, ConditionTransparencyWebhook $webhook): RedirectResponse
    {
        $this->authorize('update', $group);

        if ($webhook->group_id !== $group->id) {
            abort(404);
        }

        $webhook->delete();

        return redirect()->back()->with('success', 'Webhook removed.');
    }
}
