<?php

namespace App\Http\Controllers;

use App\Models\ConditionTimerSummaryShare;
use App\Models\Group;
use App\Services\ConditionTimerChronicleService;
use App\Services\ConditionTimerSummaryProjector;
use App\Services\ConditionTimerSummaryShareService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConditionTimerSummaryShareController extends Controller
{
    public function __construct(
        private readonly ConditionTimerSummaryShareService $shareService,
        private readonly ConditionTimerSummaryProjector $projector,
        private readonly ConditionTimerChronicleService $chronicle
    ) {
    }

    public function store(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        /** @var Authenticatable $user */
        $user = $request->user();

        $expiresInHours = $request->integer('expires_in_hours');
        $expiresAt = null;

        if ($expiresInHours !== null && $expiresInHours > 0) {
            $expiresAt = CarbonImmutable::now('UTC')->addHours($expiresInHours);
        }

        $this->shareService->createShareForGroup($group, $user, $expiresAt);

        return redirect()->back()->with('success', 'Share link generated.');
    }

    public function destroy(Group $group, ConditionTimerSummaryShare $share): RedirectResponse
    {
        $this->authorize('update', $group);

        if ($share->group_id !== $group->id) {
            abort(404);
        }

        $this->shareService->revokeShare($share);

        return redirect()->back()->with('success', 'Share link disabled.');
    }

    public function showPublic(string $token): Response
    {
        $share = ConditionTimerSummaryShare::query()
            ->where('token', $token)
            ->whereNull('deleted_at')
            ->first();

        if (! $share) {
            abort(404);
        }

        if ($share->expires_at !== null && $share->expires_at->isPast()) {
            abort(404);
        }

        $share->load('group');

        $group = $share->group;

        if (! $group) {
            abort(404);
        }

        $summary = $this->projector->projectForGroup($group);
        $summary = $this->chronicle->attachPublicTimeline($group, $summary);

        return Inertia::render('Shares/ConditionTimerSummary', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'summary' => $summary,
            'share' => [
                'created_at' => $share->created_at?->toIso8601String(),
                'expires_at' => $share->expires_at?->toIso8601String(),
            ],
        ]);
    }
}
