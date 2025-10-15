<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignTaskReorderRequest;
use App\Http\Requests\CampaignTaskStoreRequest;
use App\Http\Requests\CampaignTaskUpdateRequest;
use App\Models\Campaign;
use App\Models\CampaignTask;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CampaignTaskController extends Controller
{
    public function index(Request $request, Campaign $campaign): Response
    {
        $this->authorize('viewAny', [CampaignTask::class, $campaign]);

        /** @var Authenticatable&User $user */
        $user = $request->user();

        $tasks = $campaign->tasks()
            ->with(['assigneeUser:id,name,email', 'assigneeGroup:id,name'])
            ->orderBy('status')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $currentTurn = $campaign->region?->turns()->max('number') ?? 0;
        $turnSuggestions = $this->turnSuggestions($currentTurn);

        $columns = collect($this->statusMeta())
            ->map(fn (array $meta, string $status) => array_merge($meta, ['key' => $status]));

        $tasksByStatus = $columns->mapWithKeys(function (array $column) use ($tasks, $currentTurn, $user) {
            $status = $column['key'];

            $items = $tasks
                ->filter(fn (CampaignTask $task) => $task->status === $status)
                ->values()
                ->map(function (CampaignTask $task) use ($currentTurn, $user) {
                    $dueIn = null;
                    if ($task->due_turn_number !== null) {
                        $dueIn = max(0, $task->due_turn_number - $currentTurn);
                    }

                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'description' => $task->description,
                        'status' => $task->status,
                        'position' => $task->position,
                        'due_turn_number' => $task->due_turn_number,
                        'due_in' => $dueIn,
                        'due_at' => optional($task->due_at)->toIso8601String(),
                        'completed_at' => optional($task->completed_at)->toIso8601String(),
                        'assigned_user' => $task->assigneeUser ? [
                            'id' => $task->assigneeUser->id,
                            'name' => $task->assigneeUser->name,
                            'email' => $task->assigneeUser->email,
                        ] : null,
                        'assigned_group' => $task->assigneeGroup ? [
                            'id' => $task->assigneeGroup->id,
                            'name' => $task->assigneeGroup->name,
                        ] : null,
                        'can_update' => $user ? $user->can('update', $task) : false,
                    ];
                });

            return [$status => $items];
        });

        $members = $campaign->group
            ->memberships()
            ->with('user:id,name,email')
            ->orderBy('role')
            ->get()
            ->map(fn (GroupMembership $membership) => [
                'id' => $membership->user->id,
                'name' => $membership->user->name,
                'email' => $membership->user->email,
                'role' => $membership->role,
            ]);

        return Inertia::render('Campaigns/TaskBoard', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'status' => $campaign->status,
                'group' => [
                    'id' => $campaign->group->id,
                    'name' => $campaign->group->name,
                ],
                'current_turn' => $currentTurn,
            ],
            'columns' => $columns->values(),
            'tasks' => $tasksByStatus,
            'turn_suggestions' => $turnSuggestions,
            'members' => $members,
            'can_manage' => $user ? $user->can('update', $campaign) : false,
            'statuses' => collect($this->statusMeta())
                ->map(fn (array $meta, string $key) => [
                    'key' => $key,
                    'label' => $meta['label'],
                ])->values(),
        ]);
    }

    public function store(CampaignTaskStoreRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('create', [CampaignTask::class, $campaign]);

        /** @var Authenticatable&User $user */
        $user = $request->user();

        $data = $request->validated();
        $status = $data['status'] ?? CampaignTask::STATUS_BACKLOG;

        $this->assertAssigneeValidity($campaign, $data['assigned_user_id'] ?? null, $data['assigned_group_id'] ?? null);

        $maxPosition = $campaign->tasks()
            ->where('status', $status)
            ->max('position');
        $position = $maxPosition !== null ? ((int) $maxPosition) + 1 : 0;

        $task = $campaign->tasks()->create([
            'created_by_id' => $user->getAuthIdentifier(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $status,
            'position' => $position,
            'due_turn_number' => $data['due_turn_number'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'assigned_user_id' => $data['assigned_user_id'] ?? null,
            'assigned_group_id' => $data['assigned_group_id'] ?? null,
            'completed_at' => $status === CampaignTask::STATUS_COMPLETED ? now() : null,
        ]);

        return redirect()
            ->route('campaigns.tasks.index', $campaign)
            ->with('success', "Task '{$task->title}' added to the board.");
    }

    public function update(
        CampaignTaskUpdateRequest $request,
        Campaign $campaign,
        CampaignTask $task
    ): RedirectResponse {
        $this->ensureTaskBelongsToCampaign($campaign, $task);
        $this->authorize('update', $task);

        $data = $request->validated();

        $this->assertAssigneeValidity($campaign, $data['assigned_user_id'] ?? null, $data['assigned_group_id'] ?? null);

        DB::transaction(function () use (&$task, $data, $campaign): void {
            if (array_key_exists('status', $data) && $data['status'] !== $task->status) {
                $newStatus = $data['status'];
                $task->status = $newStatus;

                $maxPosition = $campaign->tasks()
                    ->where('status', $newStatus)
                    ->max('position');

                $task->position = $maxPosition !== null ? ((int) $maxPosition) + 1 : 0;
            }

            if (array_key_exists('completed_at', $data)) {
                $task->completed_at = $data['completed_at'];
            } elseif (($task->status === CampaignTask::STATUS_COMPLETED) && $task->completed_at === null) {
                $task->completed_at = now();
            }

            $task->fill(Arr::except($data, ['status', 'completed_at']));

            if (
                array_key_exists('status', $data)
                && $data['status'] === CampaignTask::STATUS_COMPLETED
                && $task->completed_at === null
            ) {
                $task->completed_at = now();
            }

            if (
                array_key_exists('status', $data)
                && $data['status'] !== CampaignTask::STATUS_COMPLETED
            ) {
                $task->completed_at = $data['completed_at'] ?? null;
            }

            $task->save();
        });

        return redirect()
            ->route('campaigns.tasks.index', $campaign)
            ->with('success', 'Task updated.');
    }

    public function reorder(CampaignTaskReorderRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('reorder', [CampaignTask::class, $campaign]);

        $status = $request->string('status')->toString();
        $order = $request->collect('order');

        $tasks = CampaignTask::query()
            ->whereIn('id', $order)
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($order, $tasks, $campaign, $status): void {
            $position = 0;

            foreach ($order as $taskId) {
                /** @var CampaignTask|null $task */
                $task = $tasks->get($taskId);

                if ($task === null || $task->campaign_id !== $campaign->id) {
                    throw ValidationException::withMessages([
                        'order' => 'One or more tasks are invalid for this campaign.',
                    ]);
                }

                $task->update([
                    'status' => $status,
                    'position' => $position++,
                    'completed_at' => $status === CampaignTask::STATUS_COMPLETED
                        ? $task->completed_at ?? now()
                        : null,
                ]);
            }
        });

        return redirect()
            ->route('campaigns.tasks.index', $campaign)
            ->with('success', 'Board order updated.');
    }

    public function destroy(Campaign $campaign, CampaignTask $task): RedirectResponse
    {
        $this->ensureTaskBelongsToCampaign($campaign, $task);
        $this->authorize('delete', $task);

        $title = $task->title;
        $task->delete();

        return redirect()
            ->route('campaigns.tasks.index', $campaign)
            ->with('success', "Task '{$title}' sent to the void.");
    }

    protected function ensureTaskBelongsToCampaign(Campaign $campaign, CampaignTask $task): void
    {
        if ($task->campaign_id !== $campaign->id) {
            abort(404);
        }
    }

    protected function assertAssigneeValidity(Campaign $campaign, ?int $userId, ?int $groupId): void
    {
        if ($groupId !== null && $groupId !== $campaign->group_id) {
            throw ValidationException::withMessages([
                'assigned_group_id' => 'Tasks can only be assigned to the owning group.',
            ]);
        }

        if ($userId !== null) {
            $inGroup = $campaign->group
                ->memberships()
                ->where('user_id', $userId)
                ->exists();

            if (! $inGroup) {
                throw ValidationException::withMessages([
                    'assigned_user_id' => 'Assigned adventurer must belong to the party.',
                ]);
            }
        }
    }

    /**
     * @return array<string, array{label: string, description: string, accent: string}>
     */
    protected function statusMeta(): array
    {
        return [
            CampaignTask::STATUS_BACKLOG => [
                'label' => 'Backlog',
                'description' => 'Ideas, rumors, and quests awaiting prioritization.',
                'accent' => 'border-zinc-800',
            ],
            CampaignTask::STATUS_READY => [
                'label' => 'Ready',
                'description' => 'Scoped and ready for the next turn.',
                'accent' => 'border-indigo-500/40',
            ],
            CampaignTask::STATUS_ACTIVE => [
                'label' => 'In Progress',
                'description' => 'Adventurers are currently tackling these.',
                'accent' => 'border-amber-500/60',
            ],
            CampaignTask::STATUS_REVIEW => [
                'label' => 'Review',
                'description' => 'Awaiting DM sign-off or narrative polish.',
                'accent' => 'border-sky-500/50',
            ],
            CampaignTask::STATUS_COMPLETED => [
                'label' => 'Completed',
                'description' => 'Victories logged and ready for the chronicle.',
                'accent' => 'border-emerald-500/50',
            ],
        ];
    }

    protected function turnSuggestions(int $currentTurn): Collection
    {
        $start = max(1, $currentTurn === 0 ? 1 : $currentTurn);
        $end = $start + 4;

        return collect(range($start, $end))->map(fn (int $number) => [
            'value' => $number,
            'label' => "Turn {$number}",
        ]);
    }
}
