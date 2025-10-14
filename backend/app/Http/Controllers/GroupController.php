<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroupStoreRequest;
use App\Http\Requests\GroupUpdateRequest;
use App\Models\Group;
use App\Models\GroupMembership;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Group::class, 'group');
    }

    public function index(): Response
    {
        /** @var Authenticatable&\App\Models\User $user */
        $user = auth()->user();

        $groups = $user->groups()
            ->withCount('memberships as member_count')
            ->get()
            ->map(fn (Group $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'member_count' => $group->member_count,
            ])
            ->values();

        return Inertia::render('Groups/Index', [
            'groups' => $groups,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Groups/Create');
    }

    public function store(GroupStoreRequest $request): RedirectResponse
    {
        /** @var Authenticatable&\App\Models\User $user */
        $user = $request->user();

        DB::transaction(function () use ($request, $user): void {
            $group = Group::create([
                'name' => $request->string('name')->toString(),
                'slug' => $this->generateSlug($request->string('name')->toString()),
                'description' => $request->input('description'),
                'created_by' => $user->getAuthIdentifier(),
            ]);

            GroupMembership::create([
                'group_id' => $group->id,
                'user_id' => $user->getAuthIdentifier(),
                'role' => GroupMembership::ROLE_OWNER,
            ]);
        });

        return redirect()->route('groups.index')->with('success', 'Group created.');
    }

    public function show(Group $group): Response
    {
        $group->load([
            'memberships.user:id,name,email',
            'regions.dungeonMaster:id,name',
            'regions.turnConfiguration',
            'regions.turns.processedBy:id,name',
            'campaigns:id,group_id,title,status',
        ]);

        $user = auth()->user();

        $members = $group->memberships->map(fn (GroupMembership $membership) => [
            'id' => $membership->user->id,
            'name' => $membership->user->name,
            'email' => $membership->user->email,
            'role' => $membership->role,
        ])->values();

        $regions = $group->regions->map(function ($region) use ($user) {
            $configuration = $region->turnConfiguration;

            return [
                'id' => $region->id,
                'name' => $region->name,
                'summary' => $region->summary,
                'dungeon_master' => $region->dungeonMaster ? [
                    'id' => $region->dungeonMaster->id,
                    'name' => $region->dungeonMaster->name,
                ] : null,
                'turn_configuration' => $configuration ? [
                    'turn_duration_hours' => $configuration->turn_duration_hours,
                    'next_turn_at' => optional($configuration->next_turn_at)->toAtomString(),
                    'last_processed_at' => optional($configuration->last_processed_at)->toAtomString(),
                    'is_due' => $configuration->isDue(),
                ] : null,
                'recent_turns' => $region->turns
                    ->sortByDesc('number')
                    ->take(3)
                    ->map(fn ($turn) => [
                        'id' => $turn->id,
                        'number' => $turn->number,
                        'processed_at' => optional($turn->processed_at)->toAtomString(),
                        'used_ai_fallback' => $turn->used_ai_fallback,
                        'summary' => $turn->summary,
                        'processed_by' => $turn->processedBy ? [
                            'id' => $turn->processedBy->id,
                            'name' => $turn->processedBy->name,
                        ] : null,
                    ])->values(),
                'can_process_turn' => $configuration && $user ? $user->can('update', $configuration) : false,
            ];
        })->values();

        $campaigns = $group->campaigns->map(fn ($campaign) => [
            'id' => $campaign->id,
            'title' => $campaign->title,
            'status' => $campaign->status,
        ])->values();

        return Inertia::render('Groups/Show', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'members' => $members,
                'regions' => $regions,
                'campaigns' => $campaigns,
            ],
        ]);
    }

    public function edit(Group $group): Response
    {
        return Inertia::render('Groups/Edit', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
            ],
        ]);
    }

    public function update(GroupUpdateRequest $request, Group $group): RedirectResponse
    {
        $group->update($request->validated());

        return redirect()->route('groups.show', $group)->with('success', 'Group updated.');
    }

    public function destroy(Group $group): RedirectResponse
    {
        $group->delete();

        return redirect()->route('groups.index')->with('success', 'Group removed.');
    }

    protected function generateSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'group';
        }
        $slug = $base;
        $counter = 1;

        while (Group::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
