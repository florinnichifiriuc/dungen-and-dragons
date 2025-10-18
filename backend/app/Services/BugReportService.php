<?php

namespace App\Services;

use App\Models\AiRequest;
use App\Models\BugReport;
use App\Models\BugReportUpdate;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BugReportService
{
    public function __construct(
        protected readonly AnalyticsRecorder $analytics,
        protected readonly BugWorkflowAutomationService $automation,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $submitter = null, ?Group $group = null): BugReport
    {
        $tags = array_values(array_unique(array_filter($data['tags'] ?? [])));
        $priority = $data['priority'] ?? BugReport::PRIORITY_NORMAL;

        $report = BugReport::query()->create([
            'submitted_by' => $submitter?->getKey(),
            'submitted_email' => $data['submitted_email'] ?? $submitter?->email,
            'submitted_name' => $data['submitted_name'] ?? $submitter?->name,
            'group_id' => $group?->getKey(),
            'context_type' => $data['context_type'],
            'context_identifier' => $data['context_identifier'] ?? null,
            'status' => BugReport::STATUS_OPEN,
            'priority' => $priority,
            'summary' => $data['summary'],
            'description' => $this->composeDescription($data),
            'environment' => $this->buildEnvironment($data, $submitter),
            'ai_context' => $this->buildAiContext($group, $submitter, $data['ai_focus'] ?? []),
            'tags' => $tags,
        ]);

        $report->updates()->create([
            'type' => 'created',
            'user_id' => $submitter?->getKey(),
            'payload' => [
                'summary' => $report->summary,
                'priority' => $priority,
                'context_type' => $report->context_type,
                'tags' => $tags,
            ],
        ]);

        $this->analytics->record('bug_report.created', [
            'priority' => $priority,
            'context_type' => $report->context_type,
        ], actor: $submitter, group: $group);

        $this->automation->notifyCreated($report);

        return $report;
    }

    public function updateStatus(BugReport $report, string $status, ?User $actor = null, ?string $note = null): void
    {
        $previous = $report->status;

        if ($previous === $status) {
            return;
        }

        $report->forceFill(['status' => $status])->save();

        $report->updates()->create([
            'type' => 'status_changed',
            'user_id' => $actor?->getKey(),
            'payload' => [
                'from' => $previous,
                'to' => $status,
                'note' => $note,
            ],
        ]);

        $this->automation->notifyStatusChange($report, $previous, $status);

        $this->analytics->record('bug_report.status_changed', [
            'from' => $previous,
            'to' => $status,
        ], actor: $actor, group: $report->group);
    }

    public function assign(BugReport $report, ?User $assignee, ?User $actor = null): void
    {
        $report->forceFill(['assigned_to' => $assignee?->getKey()])->save();

        $report->updates()->create([
            'type' => 'assignment',
            'user_id' => $actor?->getKey(),
            'payload' => [
                'assigned_to' => $assignee?->only(['id', 'name', 'email']),
            ],
        ]);

        $this->automation->notifyAssignment($report, $assignee);

        $this->analytics->record('bug_report.assigned', [
            'assignee' => $assignee?->getKey(),
        ], actor: $actor, group: $report->group);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateDetails(BugReport $report, array $attributes, ?User $actor = null): void
    {
        $changes = [];
        $payload = [];

        if (array_key_exists('priority', $attributes) && $attributes['priority'] !== null && $attributes['priority'] !== $report->priority) {
            $changes['priority'] = $attributes['priority'];
            $payload['priority'] = $attributes['priority'];
        }

        if (array_key_exists('tags', $attributes)) {
            $tags = array_values(array_unique(array_filter($attributes['tags'] ?? [])));

            if ($tags !== ($report->tags ?? [])) {
                $changes['tags'] = $tags;
                $payload['tags'] = $tags;
            }
        }

        if ($changes === []) {
            return;
        }

        $report->forceFill($changes)->save();

        $report->updates()->create([
            'type' => 'attributes_updated',
            'user_id' => $actor?->getKey(),
            'payload' => $payload,
        ]);

        $this->analytics->record('bug_report.updated', $payload, actor: $actor, group: $report->group);
    }

    public function addComment(BugReport $report, string $body, ?User $actor = null): BugReportUpdate
    {
        $update = $report->updates()->create([
            'type' => 'comment',
            'user_id' => $actor?->getKey(),
            'payload' => [
                'body' => $body,
            ],
        ]);

        $this->analytics->record('bug_report.commented', [
            'report' => $report->id,
        ], actor: $actor, group: $report->group);

        return $update;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function composeDescription(array $data): string
    {
        $sections = [
            $data['description'] ?? '',
        ];

        if (! empty($data['steps'])) {
            $sections[] = 'Steps to reproduce:'."\n".$data['steps'];
        }

        if (! empty($data['expected'])) {
            $sections[] = 'Expected: '.$data['expected'];
        }

        if (! empty($data['actual'])) {
            $sections[] = 'Actual: '.$data['actual'];
        }

        return trim(implode("\n\n", array_filter($sections)));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildEnvironment(array $data, ?User $submitter): array
    {
        return array_filter([
            'user_agent' => request()->userAgent(),
            'ip' => request()->ip(),
            'browser' => Arr::get($data, 'context.browser'),
            'path' => Arr::get($data, 'context.path', request()->path()),
            'locale' => Arr::get($data, 'context.locale', $submitter?->locale),
            'timezone' => $submitter?->timezone,
            'logs' => Arr::get($data, 'context.logs'),
            'extra' => Arr::get($data, 'context.extra'),
        ], fn ($value) => $value !== null && $value !== []);
    }

    /**
     * @param  array<int, string>  $focus
     * @return array<int, array<string, mixed>>
     */
    protected function buildAiContext(?Group $group, ?User $submitter, array $focus): array
    {
        if (! $group && ! $submitter) {
            return [];
        }

        $query = AiRequest::query()
            ->latest('created_at')
            ->where(function ($inner) use ($group, $submitter): void {
                if ($group) {
                    $inner->where(function ($scoped) use ($group): void {
                        $scoped->where('context_type', Group::class)
                            ->where('context_id', $group->getKey());
                    });
                }

                if ($submitter) {
                    $method = $group ? 'orWhere' : 'where';
                    $inner->{$method}('created_by', $submitter->getKey());
                }
            })
            ->limit(5);

        $requests = $query->get();

        return $requests->map(function (AiRequest $request) use ($focus) {
            $summary = Str::limit((string) $request->response_text, 200);

            return [
                'id' => $request->id,
                'type' => $request->request_type,
                'created_at' => optional($request->created_at)->toIso8601String(),
                'summary' => $summary,
                'focus_match' => $focus !== [] ? (bool) array_intersect($focus, Arr::wrap($request->meta['focus'] ?? [])) : null,
            ];
        })->all();
    }
}
