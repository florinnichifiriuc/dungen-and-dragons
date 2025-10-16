<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConditionTimerSummaryShareRequest;
use App\Http\Requests\UpdateConditionTimerSummaryShareRequest;
use App\Models\ConditionTimerSummaryShare;
use App\Models\Group;
use App\Services\ConditionTimerChronicleService;
use App\Services\ConditionTimerSummaryProjector;
use App\Services\ConditionTimerSummaryShareService;
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

    public function store(StoreConditionTimerSummaryShareRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        /** @var Authenticatable $user */
        $user = $request->user();

        $expiresAt = $request->resolveExpiresAt();

        $this->shareService->createShareForGroup($group, $user, $expiresAt);

        return redirect()->back()->with('success', 'Share link generated.');
    }

    public function update(
        UpdateConditionTimerSummaryShareRequest $request,
        Group $group,
        ConditionTimerSummaryShare $share
    ): RedirectResponse {
        $this->authorize('update', $group);

        if ($share->group_id !== $group->id) {
            abort(404);
        }

        if ($share->deleted_at !== null) {
            abort(404);
        }

        $expiresAt = $request->resolveExpiresAt();

        $this->shareService->extendShare($share, $expiresAt);

        return redirect()->back()->with('success', 'Share expiry updated.');
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

    public function showPublic(Request $request, string $token): Response
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

        $this->shareService->recordAccess($share, $request->ip(), $request->userAgent());

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
