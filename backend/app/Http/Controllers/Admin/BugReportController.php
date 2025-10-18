<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BugReportAdminUpdateRequest;
use App\Http\Requests\BugReportCommentRequest;
use App\Models\BugReport;
use App\Models\User;
use App\Services\BugReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BugReportController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', BugReport::class);

        $query = BugReport::query()->with(['group', 'assignee'])->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($priority = $request->string('priority')->toString()) {
            $query->where('priority', $priority);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('reference', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        $timeframe = $request->string('timeframe')->toString();

        if ($timeframe) {
            $since = $this->resolveTimeframe($timeframe);

            if ($since !== null) {
                $query->where('updated_at', '>=', $since);
            }
        }

        $reports = $query->paginate(20)->withQueryString();

        $statusCounts = BugReport::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $priorityCounts = BugReport::query()
            ->selectRaw('priority, COUNT(*) as aggregate')
            ->groupBy('priority')
            ->pluck('aggregate', 'priority');

        $analytics = $this->buildAnalytics();

        return Inertia::render('Admin/BugReports/Index', [
            'filters' => [
                'status' => $status,
                'priority' => $priority,
                'search' => $search,
                'timeframe' => $timeframe,
            ],
            'reports' => $reports->through(fn (BugReport $report) => [
                'id' => $report->id,
                'reference' => $report->reference,
                'summary' => $report->summary,
                'priority' => $report->priority,
                'status' => $report->status,
                'group' => $report->group?->only(['id', 'name']),
                'assignee' => $report->assignee?->only(['id', 'name']),
                'updated_at' => optional($report->updated_at)->toIso8601String(),
            ]),
            'counts' => [
                'status' => $statusCounts,
                'priority' => $priorityCounts,
            ],
            'analytics' => $analytics,
        ]);
    }

    public function show(Request $request, BugReport $bugReport): Response
    {
        Gate::authorize('view', $bugReport);

        $bugReport->load(['updates.actor', 'assignee', 'group']);

        $supportAdmins = User::query()->where('is_support_admin', true)->get(['id', 'name', 'email']);

        return Inertia::render('Admin/BugReports/Show', [
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
                'submitted_at' => optional($bugReport->created_at)->toIso8601String(),
                'submitter' => [
                    'name' => $bugReport->submitted_name,
                    'email' => $bugReport->submitted_email,
                ],
                'assignee' => $bugReport->assignee?->only(['id', 'name', 'email']),
                'group' => $bugReport->group?->only(['id', 'name']),
                'updates' => $bugReport->updates
                    ->sortByDesc('created_at')
                    ->map(fn ($update) => [
                        'id' => $update->id,
                        'type' => $update->type,
                        'payload' => $update->payload,
                        'created_at' => optional($update->created_at)->toIso8601String(),
                        'actor' => $update->actor?->only(['id', 'name', 'email']),
                    ])->values(),
            ],
            'support_admins' => $supportAdmins,
        ]);
    }

    public function update(BugReportAdminUpdateRequest $request, BugReportService $service, BugReport $bugReport): RedirectResponse
    {
        Gate::authorize('update', $bugReport);

        $data = $request->validated();

        if (isset($data['status'])) {
            $service->updateStatus($bugReport, $data['status'], $request->user(), $data['note'] ?? null);
        }

        $service->updateDetails($bugReport, $data, $request->user());

        if (array_key_exists('assigned_to', $data)) {
            $assignee = $data['assigned_to'] ? User::query()->find($data['assigned_to']) : null;
            $service->assign($bugReport, $assignee, $request->user());
        }

        return back()->with('flash.banner', __('Bug report updated.'))->with('flash.bannerStyle', 'success');
    }

    public function comment(BugReportCommentRequest $request, BugReportService $service, BugReport $bugReport): RedirectResponse
    {
        Gate::authorize('addComment', $bugReport);

        $service->addComment($bugReport, $request->input('body'), $request->user());

        return back()->with('flash.banner', __('Comment recorded.'))->with('flash.bannerStyle', 'success');
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        Gate::authorize('viewAny', BugReport::class);

        $reports = BugReport::query()
            ->with(['group', 'assignee'])
            ->orderBy('created_at')
            ->get();

        $callback = function () use ($reports): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Summary', 'Priority', 'Status', 'Group', 'Assignee', 'Created']);

            foreach ($reports as $report) {
                fputcsv($out, [
                    $report->reference,
                    $report->summary,
                    $report->priority,
                    $report->status,
                    $report->group?->name,
                    $report->assignee?->name,
                    optional($report->created_at)->toIso8601String(),
                ]);
            }

            fclose($out);
        };

        $filename = 'bug-reports-'.Carbon::now('UTC')->format('Ymd-His').'.csv';

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAnalytics(): array
    {
        $now = CarbonImmutable::now('UTC');
        $windowStart = $now->subDays(6)->startOfDay();

        $volumeSeries = $this->buildVolumeSeries($windowStart);
        $currentTotal = collect($volumeSeries)->sum('count');

        $previousStart = $windowStart->subDays(7);
        $previousEnd = $windowStart->subSecond();

        $previousTotal = BugReport::query()
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $delta = null;

        if ($previousTotal > 0) {
            $delta = round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1);
        }

        $resolutionStats = $this->buildResolutionStats($now);
        $categoryTrends = $this->buildCategoryTrends($now);

        return [
            'volume' => [
                'series' => $volumeSeries,
                'current_total' => $currentTotal,
                'previous_total' => $previousTotal,
                'delta_percentage' => $delta,
            ],
            'resolution' => $resolutionStats,
            'categories' => $categoryTrends,
        ];
    }

    /**
     * @return array<int, array{day: string, count: int}>
     */
    protected function buildVolumeSeries(CarbonImmutable $start): array
    {
        $volume = BugReport::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('aggregate', 'day');

        $series = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $start->addDays($i)->toDateString();
            $series[] = [
                'day' => $day,
                'count' => (int) ($volume[$day] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * @return array<string, float|null>
     */
    protected function buildResolutionStats(CarbonImmutable $now): array
    {
        $reports = BugReport::query()
            ->whereIn('status', [BugReport::STATUS_RESOLVED, BugReport::STATUS_CLOSED])
            ->where('updated_at', '>=', $now->subDays(30))
            ->get(['created_at', 'updated_at']);

        $durations = $reports
            ->map(fn (BugReport $report) => optional($report->created_at)?->diffInMinutes(optional($report->updated_at) ?? $now))
            ->filter(fn ($minutes) => $minutes !== null)
            ->values();

        if ($durations->isEmpty()) {
            return [
                'average_hours' => null,
                'median_hours' => null,
                'p90_hours' => null,
            ];
        }

        $average = round($durations->avg() / 60, 1);
        $median = round($durations->median() / 60, 1);
        $p90 = round($this->percentile($durations, 0.9) / 60, 1);

        return [
            'average_hours' => $average,
            'median_hours' => $median,
            'p90_hours' => $p90,
        ];
    }

    /**
     * @param  Collection<int, int>  $values
     */
    protected function percentile(Collection $values, float $percentile): float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return 0.0;
        }

        $index = (int) ceil($percentile * $count) - 1;
        $index = max(0, min($index, $count - 1));

        return (float) $sorted->get($index);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildCategoryTrends(CarbonImmutable $now): array
    {
        $reports = BugReport::query()
            ->where('created_at', '>=', $now->subDays(30))
            ->get(['tags']);

        $counts = [];

        foreach ($reports as $report) {
            foreach ($report->tags ?? [] as $tag) {
                $tagKey = strtolower($tag);
                $counts[$tagKey] = ($counts[$tagKey] ?? 0) + 1;
            }
        }

        arsort($counts);

        $top = array_slice($counts, 0, 5, true);

        return [
            'top' => array_map(
                static fn ($tag, $count) => ['tag' => $tag, 'count' => $count],
                array_keys($top),
                $top
            ),
            'total' => array_sum($counts),
        ];
    }

    protected function resolveTimeframe(string $timeframe): ?CarbonImmutable
    {
        $now = CarbonImmutable::now('UTC');

        return match ($timeframe) {
            '24h' => $now->subHours(24),
            '7d' => $now->subDays(7),
            '30d' => $now->subDays(30),
            default => null,
        };
    }
}
