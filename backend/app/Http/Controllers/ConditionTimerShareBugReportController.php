<?php

namespace App\Http\Controllers;

use App\Http\Requests\BugReportStoreRequest;
use App\Models\ConditionTimerSummaryShare;
use App\Services\BugReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConditionTimerShareBugReportController extends Controller
{
    public function create(Request $request, string $token): Response
    {
        $share = $this->resolveShare($token);

        return Inertia::render('Shares/ConditionTimerBugReport', [
            'share' => [
                'token' => $share->token,
                'group' => $share->group ? $share->group->only(['id', 'name']) : null,
                'context_identifier' => $request->input('context_identifier'),
            ],
            'prefill' => [
                'summary' => $request->input('summary'),
                'description' => $request->input('description'),
            ],
        ]);
    }

    public function store(BugReportStoreRequest $request, BugReportService $service, string $token): RedirectResponse
    {
        $share = $this->resolveShare($token);
        $group = $share->group;

        $data = $request->validated();
        $data['context_identifier'] = $token;
        $data['context_type'] = 'player_share';

        $report = $service->create($data, $request->user(), $group);

        return redirect()
            ->route('shares.condition-timers.player-summary.show', $token)
            ->with('flash.banner', __('Thanks! We captured your report (:reference).', ['reference' => $report->reference]))
            ->with('flash.bannerStyle', 'success');
    }

    protected function resolveShare(string $token): ConditionTimerSummaryShare
    {
        $share = ConditionTimerSummaryShare::query()
            ->where('token', $token)
            ->whereNull('deleted_at')
            ->first();

        if (! $share || $share->group === null) {
            abort(404);
        }

        if ($share->expires_at !== null && $share->expires_at->isPast()) {
            if ($share->expires_at->addHours(48)->isPast()) {
                abort(404);
            }
        }

        $snapshot = (array) $share->consent_snapshot;
        $grantedSnapshot = array_map('intval', $snapshot['granted_user_ids'] ?? []);
        $currentConsenting = app(\App\Services\ConditionTimerShareConsentService::class)
            ->consentingUserIds($share->group, $share->visibility_mode ?? 'counts');

        sort($grantedSnapshot);
        $currentConsenting = array_map('intval', $currentConsenting);
        sort($currentConsenting);

        if ($grantedSnapshot !== [] && array_diff($grantedSnapshot, $currentConsenting) !== []) {
            abort(403, 'Consent has been revoked for this share.');
        }

        return $share->load('group');
    }
}
