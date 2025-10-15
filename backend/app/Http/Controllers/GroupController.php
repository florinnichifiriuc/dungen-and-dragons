<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroupStoreRequest;
use App\Http\Requests\GroupUpdateRequest;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\World;
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
                'join_code' => $this->generateJoinCode(),
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
            'worlds' => function ($query): void {
                $query->with([
                    'regions.dungeonMaster:id,name',
                    'regions.turnConfiguration',
                    'regions.turns.processedBy:id,name',
                ]);
            },
            'campaigns:id,group_id,title,status',
            'tileTemplates' => function ($query): void {
                $query->with(['creator:id,name', 'world:id,name']);
            },
            'maps' => function ($query): void {
                $query->with(['region:id,name'])->withCount('tiles');
            },
        ]);

        $user = auth()->user();

        $viewerMembership = null;

        if ($user !== null) {
            $viewerMembership = $group->memberships
                ->firstWhere('user_id', $user->getAuthIdentifier());
        }

        $canManageMembers = $viewerMembership !== null
            && in_array($viewerMembership->role, [
                GroupMembership::ROLE_OWNER,
                GroupMembership::ROLE_DUNGEON_MASTER,
            ], true);

        $canPromoteToOwner = $viewerMembership !== null
            && $viewerMembership->role === GroupMembership::ROLE_OWNER;

        $members = $group->memberships->map(fn (GroupMembership $membership) => [
            'id' => $membership->id,
            'user_id' => $membership->user->id,
            'name' => $membership->user->name,
            'email' => $membership->user->email,
            'role' => $membership->role,
            'is_viewer' => $user !== null
                && $membership->user_id === $user->getAuthIdentifier(),
        ])->values();

        $regions = $group->regions->map(function ($region) use ($user) {
            $configuration = $region->turnConfiguration;

            return [
                'id' => $region->id,
                'world_id' => $region->world_id,
                'name' => $region->name,
                'summary' => $region->summary,
                'ai_controlled' => (bool) $region->ai_controlled,
                'ai_delegate_summary' => $region->ai_delegate_summary,
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
                'can_delegate_to_ai' => $user ? $user->can('delegateToAi', $region) : false,
            ];
        })->values();

        $worlds = $group->worlds->map(function (World $world) use ($user) {
            return [
                'id' => $world->id,
                'name' => $world->name,
                'summary' => $world->summary,
                'description' => $world->description,
                'default_turn_duration_hours' => $world->default_turn_duration_hours,
                'regions' => $world->regions->map(function ($region) use ($user) {
                    $configuration = $region->turnConfiguration;

                    return [
                        'id' => $region->id,
                        'name' => $region->name,
                        'summary' => $region->summary,
                        'ai_controlled' => (bool) $region->ai_controlled,
                        'ai_delegate_summary' => $region->ai_delegate_summary,
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
                        'can_delegate_to_ai' => $user ? $user->can('delegateToAi', $region) : false,
                    ];
                })->values(),
            ];
        })->values();

        $campaigns = $group->campaigns->map(fn ($campaign) => [
            'id' => $campaign->id,
            'title' => $campaign->title,
            'status' => $campaign->status,
        ])->values();

        $tileTemplates = $group->tileTemplates->map(fn ($template) => [
            'id' => $template->id,
            'name' => $template->name,
            'terrain_type' => $template->terrain_type,
            'movement_cost' => $template->movement_cost,
            'defense_bonus' => $template->defense_bonus,
            'key' => $template->key,
            'world' => $template->world_id ? [
                'id' => $template->world_id,
                'name' => optional($template->world)->name,
            ] : null,
            'creator' => $template->creator ? [
                'id' => $template->creator->id,
                'name' => $template->creator->name,
            ] : null,
        ])->values();

        $maps = $group->maps->map(fn ($map) => [
            'id' => $map->id,
            'title' => $map->title,
            'base_layer' => $map->base_layer,
            'orientation' => $map->orientation,
            'tile_count' => $map->tiles_count,
            'region' => $map->region ? [
                'id' => $map->region->id,
                'name' => $map->region->name,
            ] : null,
        ])->values();

        return Inertia::render('Groups/Show', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'members' => $members,
                'worlds' => $worlds,
                'regions' => $regions,
                'campaigns' => $campaigns,
                'tile_templates' => $tileTemplates,
                'maps' => $maps,
            ],
            'viewer_membership' => $viewerMembership ? [
                'id' => $viewerMembership->id,
                'role' => $viewerMembership->role,
            ] : null,
            'permissions' => [
                'manage_members' => $canManageMembers,
                'promote_to_owner' => $canPromoteToOwner,
            ],
            'join_code' => $canManageMembers ? $group->join_code : null,
            'role_options' => GroupMembership::roles(),
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

    protected function generateJoinCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Group::where('join_code', $code)->exists());

        return $code;
    }
}
