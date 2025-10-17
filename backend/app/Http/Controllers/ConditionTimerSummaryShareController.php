<?php

namespace App\Http\Controllers;

use App\Exceptions\ConditionTimerShareConsentException;
use App\Http\Requests\ConditionTimerSummaryShareExtendRequest;
use App\Http\Requests\ConditionTimerSummaryShareStoreRequest;
use App\Models\ConditionTimerSummaryShare;
use App\Models\Group;
use App\Services\ConditionTimerChronicleService;
use App\Services\ConditionTimerShareConsentService;
use App\Services\ConditionTimerSummaryProjector;
use App\Services\ConditionTimerSummaryShareService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConditionTimerSummaryShareController extends Controller
{
    public function __construct(
        private readonly ConditionTimerSummaryShareService $shareService,
        private readonly ConditionTimerSummaryProjector $projector,
        private readonly ConditionTimerChronicleService $chronicle,
        private readonly ConditionTimerShareConsentService $consents
    ) {
    }

    public function extend(
        ConditionTimerSummaryShareExtendRequest $request,
        Group $group,
        ConditionTimerSummaryShare $share
    ): RedirectResponse {
        $this->authorize('update', $group);

        if ($share->group_id !== $group->id) {
            abort(404);
        }

        $never = $request->shouldNeverExpire();
        $hours = $request->extensionHours();

        $base = $share->expires_at && $share->expires_at->isFuture()
            ? $share->expires_at
            : CarbonImmutable::now('UTC');

        $nextExpiry = null;

        if (! $never) {
            $hours = $hours ?? 24;
            $nextExpiry = $base->addHours($hours);
        }

        $this->shareService->extendShare($share, $nextExpiry, $never);

        return redirect()->back()->with('success', 'Share expiry updated.');
    }

    public function store(ConditionTimerSummaryShareStoreRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        /** @var Authenticatable $user */
        $user = $request->user();

        $hours = $request->expiresInHours();
        $neverExpires = $request->shouldNeverExpire();
        $expiresAt = null;

        if (! $neverExpires && $hours !== null && $hours > 0) {
            $expiresAt = CarbonImmutable::now('UTC')->addHours($hours);
        }

        $visibilityMode = $request->input('visibility_mode', 'counts');

        try {
            $this->shareService->createShareForGroup($group, $user, $expiresAt, $visibilityMode, $neverExpires);
        } catch (ConditionTimerShareConsentException $exception) {
            $names = $exception->missing->pluck('user_name')->filter()->values()->all();

            return redirect()
                ->back()
                ->withErrors([
                    'share' => 'Consent missing for: '.implode(', ', $names),
                ]);
        }

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

        $redacted = false;

        if ($share->expires_at !== null && $share->expires_at->isPast()) {
            if ($share->expires_at->addHours(48)->isPast()) {
                $redacted = true;
            } else {
                abort(404);
            }
        }

        $share->load('group');

        $group = $share->group;

        if (! $group) {
            abort(404);
        }

        $currentConsenting = $this->consents->consentingUserIds($group, $share->visibility_mode ?? 'counts');
        $snapshot = (array) $share->consent_snapshot;
        $grantedSnapshot = array_map('intval', $snapshot['granted_user_ids'] ?? []);

        sort($currentConsenting);
        sort($grantedSnapshot);

        if ($grantedSnapshot !== [] && array_diff($grantedSnapshot, $currentConsenting) !== []) {
            abort(403, 'Consent has been revoked for this share.');
        }

        $summary = $this->projector->projectForGroup($group);
        $summary = $this->chronicle->attachPublicTimeline($group, $summary);

        if ($redacted) {
            $summary['entries'] = [];
        }

        if (($share->visibility_mode ?? 'counts') === 'counts') {
            $summary['entries'] = array_map(function (array $entry) {
                $entry['conditions'] = array_map(function (array $condition) {
                    unset($condition['timeline']);
                    return $condition;
                }, $entry['conditions'] ?? []);

                return $entry;
            }, $summary['entries'] ?? []);
        }

        $this->shareService->recordAccess($share, request());

        return Inertia::render('Shares/ConditionTimerSummary', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'summary' => $summary,
            'share' => [
                'created_at' => $share->created_at?->toIso8601String(),
                'expires_at' => $share->expires_at?->toIso8601String(),
                'visibility_mode' => $share->visibility_mode,
                'access_count' => $share->access_count,
                'state' => $this->shareService->describeShareState($share),
                'redacted' => $redacted,
            ],
        ]);
    }
}
