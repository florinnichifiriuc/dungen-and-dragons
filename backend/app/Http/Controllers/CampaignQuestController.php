<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignQuestStoreRequest;
use App\Http\Requests\CampaignQuestUpdateRequest;
use App\Models\Campaign;
use App\Models\CampaignQuest;
use App\Models\CampaignQuestUpdate;
use App\Models\Region;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CampaignQuestController extends Controller
{
    public function index(Request $request, Campaign $campaign): Response
    {
        $this->authorize('viewAny', [CampaignQuest::class, $campaign]);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => $request->string('status')->toString(),
            'priority' => $request->string('priority')->toString(),
            'region' => $request->string('region')->toString(),
            'include_archived' => $request->boolean('include_archived', false),
        ];

        $questsQuery = $campaign->quests()
            ->with([
                'region:id,name',
                'creator:id,name',
                'updates' => function ($query): void {
                    $query->latest('recorded_at')
                        ->latest()
                        ->with('author:id,name')
                        ->limit(1);
                },
            ]);

        if (! $filters['include_archived']) {
            $questsQuery->whereNull('archived_at');
        }

        if ($filters['search'] !== '') {
            $like = '%'.addcslashes($filters['search'], '%_').'%';
            $questsQuery->where(function (Builder $builder) use ($like): void {
                $builder->where('title', 'like', $like)
                    ->orWhere('summary', 'like', $like)
                    ->orWhere('details', 'like', $like);
            });
        }

        if ($filters['status'] !== '') {
            $questsQuery->where('status', $filters['status']);
        }

        if ($filters['priority'] !== '') {
            $questsQuery->where('priority', $filters['priority']);
        }

        if ($filters['region'] !== '') {
            $questsQuery->where('region_id', $filters['region']);
        }

        $quests = $questsQuery
            ->orderByRaw($this->priorityOrderExpression())
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (CampaignQuest $quest) {
                $latestUpdate = $quest->updates->first();

                return [
                    'id' => $quest->id,
                    'title' => $quest->title,
                    'summary' => Str::limit((string) $quest->summary, 220),
                    'status' => $quest->status,
                    'priority' => $quest->priority,
                    'region' => $quest->region ? [
                        'id' => $quest->region->id,
                        'name' => $quest->region->name,
                    ] : null,
                    'updated_at' => $quest->updated_at?->toIso8601String(),
                    'latest_update' => $latestUpdate ? [
                        'id' => $latestUpdate->id,
                        'summary' => $latestUpdate->summary,
                        'recorded_at' => $latestUpdate->recorded_at?->toIso8601String(),
                        'author' => $latestUpdate->author ? [
                            'id' => $latestUpdate->author->id,
                            'name' => $latestUpdate->author->name,
                        ] : null,
                    ] : null,
                ];
            });

        $regions = Region::query()
            ->where('group_id', $campaign->group_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('CampaignQuests/Index', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'group' => [
                    'id' => $campaign->group->id,
                    'name' => $campaign->group->name,
                ],
            ],
            'quests' => $quests,
            'filters' => $filters,
            'available_statuses' => CampaignQuest::statuses(),
            'available_priorities' => CampaignQuest::priorities(),
            'regions' => $regions,
        ]);
    }

    public function create(Campaign $campaign): Response
    {
        $this->authorize('create', [CampaignQuest::class, $campaign]);

        $regions = $campaign->group->regions()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('CampaignQuests/Create', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ],
            'available_statuses' => CampaignQuest::statuses(),
            'available_priorities' => CampaignQuest::priorities(),
            'regions' => $regions,
        ]);
    }

    public function store(CampaignQuestStoreRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('create', [CampaignQuest::class, $campaign]);

        /** @var Authenticatable $user */
        $user = $request->user();

        $data = $request->validated();

        $quest = $campaign->quests()->create(array_merge($data, [
            'created_by_id' => $user->getAuthIdentifier(),
        ]));

        return redirect()
            ->route('campaigns.quests.show', [$campaign, $quest])
            ->with('success', 'Quest created.');
    }

    public function show(Campaign $campaign, CampaignQuest $quest): Response
    {
        $this->assertQuestCampaign($campaign, $quest);
        $this->authorize('view', $quest);

        $quest->load([
            'region:id,name',
            'creator:id,name',
            'updates' => fn ($query) => $query->with('author:id,name')->orderByDesc('recorded_at')->orderByDesc('created_at'),
        ]);

        return Inertia::render('CampaignQuests/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ],
            'quest' => [
                'id' => $quest->id,
                'title' => $quest->title,
                'summary' => $quest->summary,
                'details' => $quest->details,
                'status' => $quest->status,
                'priority' => $quest->priority,
                'target_turn_number' => $quest->target_turn_number,
                'starts_at' => $quest->starts_at?->toIso8601String(),
                'completed_at' => $quest->completed_at?->toIso8601String(),
                'archived_at' => $quest->archived_at?->toIso8601String(),
                'region' => $quest->region ? [
                    'id' => $quest->region->id,
                    'name' => $quest->region->name,
                ] : null,
                'creator' => $quest->creator ? [
                    'id' => $quest->creator->id,
                    'name' => $quest->creator->name,
                ] : null,
            ],
            'updates' => $quest->updates->map(fn ($update) => [
                'id' => $update->id,
                'summary' => $update->summary,
                'details' => $update->details,
                'recorded_at' => $update->recorded_at?->toIso8601String(),
                'created_at' => $update->created_at?->toIso8601String(),
                'author' => $update->author ? [
                    'id' => $update->author->id,
                    'name' => $update->author->name,
                ] : null,
                'can_delete' => $request->user()?->can('delete', $update) ?? false,
            ])->values(),
            'available_statuses' => CampaignQuest::statuses(),
            'available_priorities' => CampaignQuest::priorities(),
            'regions' => $campaign->group->regions()->orderBy('name')->get(['id', 'name']),
            'permissions' => [
                'can_update' => $request->user()?->can('update', $quest) ?? false,
                'can_delete' => $request->user()?->can('delete', $quest) ?? false,
                'can_log_update' => $request->user()?->can('create', [CampaignQuestUpdate::class, $quest]) ?? false,
            ],
        ]);
    }

    public function edit(Campaign $campaign, CampaignQuest $quest): Response
    {
        $this->assertQuestCampaign($campaign, $quest);
        $this->authorize('update', $quest);

        $quest->loadMissing('region:id,name');

        return Inertia::render('CampaignQuests/Edit', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ],
            'quest' => [
                'id' => $quest->id,
                'title' => $quest->title,
                'summary' => $quest->summary,
                'details' => $quest->details,
                'status' => $quest->status,
                'priority' => $quest->priority,
                'region_id' => $quest->region_id,
                'target_turn_number' => $quest->target_turn_number,
                'starts_at' => $quest->starts_at?->format('Y-m-d\TH:i'),
                'completed_at' => $quest->completed_at?->format('Y-m-d\TH:i'),
                'archived_at' => $quest->archived_at?->format('Y-m-d\TH:i'),
            ],
            'available_statuses' => CampaignQuest::statuses(),
            'available_priorities' => CampaignQuest::priorities(),
            'regions' => $campaign->group->regions()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(CampaignQuestUpdateRequest $request, Campaign $campaign, CampaignQuest $quest): RedirectResponse
    {
        $this->assertQuestCampaign($campaign, $quest);
        $this->authorize('update', $quest);

        $quest->update($request->validated());

        return redirect()
            ->route('campaigns.quests.show', [$campaign, $quest])
            ->with('success', 'Quest updated.');
    }

    public function destroy(Campaign $campaign, CampaignQuest $quest): RedirectResponse
    {
        $this->assertQuestCampaign($campaign, $quest);
        $this->authorize('delete', $quest);

        $quest->delete();

        return redirect()
            ->route('campaigns.quests.index', $campaign)
            ->with('success', 'Quest removed.');
    }

    protected function assertQuestCampaign(Campaign $campaign, CampaignQuest $quest): void
    {
        if ($quest->campaign_id !== $campaign->id) {
            abort(404);
        }
    }

    protected function priorityOrderExpression(): string
    {
        $priorities = CampaignQuest::priorities();

        $cases = collect($priorities)
            ->map(fn ($priority, $index) => "WHEN '".$priority."' THEN ".$index)
            ->implode(' ');

        return "CASE priority {$cases} ELSE ".count($priorities).' END';
    }
}
