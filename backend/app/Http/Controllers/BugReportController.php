<?php

namespace App\Http\Controllers;

use App\Http\Requests\BugReportStoreRequest;
use App\Models\BugReport;
use App\Models\BugReportUpdate;
use App\Models\Group;
use App\Services\BugReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BugReportController extends Controller
{
    public function create(Request $request): Response
    {
        $this->authorize('create', BugReport::class);

        $group = null;

        if ($request->filled('group_id')) {
            $group = Group::query()->find($request->input('group_id'));
        }

        $contextType = $request->string('context_type')->toString() ?: 'facilitator';

        return Inertia::render('BugReports/Create', [
            'group' => $group ? [
                'id' => $group->id,
                'name' => $group->name,
            ] : null,
            'context' => [
                'type' => $contextType,
                'identifier' => $request->input('context_identifier'),
                'path' => $request->input('path'),
            ],
            'prefill' => [
                'summary' => $request->input('summary'),
                'description' => $request->input('description'),
                'steps' => $request->input('steps'),
            ],
        ]);
    }

    public function store(BugReportStoreRequest $request, BugReportService $service): RedirectResponse
    {
        $user = $request->user();
        $group = null;

        if ($request->filled('group_id')) {
            $group = Group::query()->find($request->input('group_id'));
        }

        $report = $service->create($request->validated(), $user, $group);

        return redirect()
            ->route('bug-reports.show', $report)
            ->with('flash.banner', __('Thanks! We logged bug report :reference.', ['reference' => $report->reference]))
            ->with('flash.bannerStyle', 'success');
    }

    public function show(Request $request, BugReport $bugReport): Response
    {
        $this->authorize('view', $bugReport);

        $bugReport->load(['assignee', 'group', 'updates.actor']);

        return Inertia::render('BugReports/Show', [
            'report' => [
                'id' => $bugReport->id,
                'reference' => $bugReport->reference,
                'summary' => $bugReport->summary,
                'description' => $bugReport->description,
                'status' => $bugReport->status,
                'priority' => $bugReport->priority,
                'tags' => $bugReport->tags ?? [],
                'context_type' => $bugReport->context_type,
                'context_identifier' => $bugReport->context_identifier,
                'environment' => $bugReport->environment,
                'ai_context' => $bugReport->ai_context,
                'submitted' => [
                    'name' => $bugReport->submitted_name,
                    'email' => $bugReport->submitted_email,
                    'at' => optional($bugReport->created_at)->toIso8601String(),
                ],
                'assignee' => $bugReport->assignee ? $bugReport->assignee->only(['id', 'name', 'email']) : null,
                'group' => $bugReport->group ? $bugReport->group->only(['id', 'name']) : null,
                'updates' => $bugReport->updates
                    ->sortByDesc('created_at')
                    ->map(fn (BugReportUpdate $update) => [
                        'id' => $update->id,
                        'type' => $update->type,
                        'payload' => $update->payload,
                        'created_at' => optional($update->created_at)->toIso8601String(),
                        'actor' => $update->actor?->only(['id', 'name', 'email']),
                    ])->values(),
            ],
            'can' => [
                'update' => $request->user()?->can('update', $bugReport) ?? false,
            ],
        ]);
    }
}
