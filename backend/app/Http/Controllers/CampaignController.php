<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignStoreRequest;
use App\Http\Requests\CampaignUpdateRequest;
use App\Models\Campaign;
use App\Models\CampaignRoleAssignment;
use App\Models\CampaignEntity;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Region;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Campaign::class);

        /** @var Authenticatable&User $user */
        $user = auth()->user();

        $groupIds = $user->groups()->pluck('groups.id');

        $campaigns = Campaign::query()
            ->with(['group:id,name', 'region:id,name'])
            ->where(function (Builder $query) use ($user, $groupIds): void {
                $query->whereIn('group_id', $groupIds)
                    ->orWhereHas('roleAssignments', function (Builder $assignments) use ($user): void {
                        $assignments
                            ->where('assignee_type', User::class)
                            ->where('assignee_id', $user->getAuthIdentifier());
                    })
                    ->orWhereHas('roleAssignments', function (Builder $assignments) use ($groupIds): void {
                        $assignments
                            ->where('assignee_type', Group::class)
                            ->whereIn('assignee_id', $groupIds);
                    });
            })
            ->orderByDesc('created_at')
            ->get()
            ->unique('id')
            ->values()
            ->map(fn (Campaign $campaign) => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'status' => $campaign->status,
                'group' => [
                    'id' => $campaign->group->id,
                    'name' => $campaign->group->name,
                ],
                'region' => $campaign->region ? [
                    'id' => $campaign->region->id,
                    'name' => $campaign->region->name,
                ] : null,
            ]);

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Campaign::class);

        /** @var Authenticatable&User $user */
        $user = auth()->user();

        $memberships = $user->groupMemberships()
            ->whereIn('role', [GroupMembership::ROLE_OWNER, GroupMembership::ROLE_DUNGEON_MASTER])
            ->with('group.regions:id,group_id,name')
            ->get();

        $groups = $memberships->map(function (GroupMembership $membership) {
            $group = $membership->group;

            return [
                'id' => $group->id,
                'name' => $group->name,
                'regions' => $group->regions->map(fn (Region $region) => [
                    'id' => $region->id,
                    'name' => $region->name,
                ])->values(),
            ];
        })->values();

        return Inertia::render('Campaigns/Create', [
            'groups' => $groups,
            'available_statuses' => Campaign::statuses(),
        ]);
    }

    public function store(CampaignStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        /** @var Authenticatable&User $user */
        $user = $request->user();

        $group = Group::query()->findOrFail($request->integer('group_id'));
        $this->assertUserManagesGroup($user, $group);

        $regionId = $request->integer('region_id');
        if ($regionId !== 0 && $regionId !== null) {
            $region = Region::query()
                ->where('group_id', $group->id)
                ->where('id', $regionId)
                ->first();

            if ($region === null) {
                throw ValidationException::withMessages([
                    'region_id' => 'Selected region must belong to the chosen group.',
                ]);
            }
        }

        $campaign = null;

        DB::transaction(function () use ($request, $user, $group, &$campaign): void {
            $campaign = Campaign::create([
                'group_id' => $group->id,
                'region_id' => $request->integer('region_id') ?: null,
                'created_by' => $user->getAuthIdentifier(),
                'title' => $request->string('title')->toString(),
                'slug' => $this->generateSlug($request->string('title')->toString()),
                'synopsis' => $request->input('synopsis'),
                'status' => $request->string('status')->isNotEmpty()
                    ? $request->string('status')->toString()
                    : Campaign::STATUS_PLANNING,
                'default_timezone' => $request->string('default_timezone')->toString(),
                'start_date' => $request->date('start_date'),
                'end_date' => $request->date('end_date'),
                'turn_hours' => $request->input('turn_hours'),
            ]);

            $campaign->roleAssignments()->create([
                'assignee_type' => User::class,
                'assignee_id' => $user->getAuthIdentifier(),
                'role' => CampaignRoleAssignment::ROLE_GM,
                'scope' => 'campaign',
                'status' => CampaignRoleAssignment::STATUS_ACTIVE,
                'assigned_by' => $user->getAuthIdentifier(),
                'accepted_at' => now(),
            ]);
        });

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created.');
    }

    public function show(Campaign $campaign): Response
    {
        $this->authorize('view', $campaign);

        $campaign->load([
            'group.memberships.user:id,name,email',
            'region:id,name',
            'invitations.group:id,name',
            'roleAssignments.assignee',
        ]);

        $entityCount = $campaign->entities()->count();
        $recentEntities = $campaign->entities()
            ->latest()
            ->take(3)
            ->get(['id', 'name', 'entity_type'])
            ->map(fn (CampaignEntity $entity) => [
                'id' => $entity->id,
                'name' => $entity->name,
                'entity_type' => $entity->entity_type,
            ])
            ->values();

        $members = $campaign->group->memberships->map(fn (GroupMembership $membership) => [
            'id' => $membership->user->id,
            'name' => $membership->user->name,
            'email' => $membership->user->email,
            'role' => $membership->role,
        ])->values();

        $assignments = $campaign->roleAssignments->map(function (CampaignRoleAssignment $assignment) {
            $assignee = $assignment->assignee;

            return [
                'id' => $assignment->id,
                'role' => $assignment->role,
                'status' => $assignment->status,
                'assignee' => match (true) {
                    $assignee instanceof User => [
                        'type' => 'user',
                        'id' => $assignee->id,
                        'name' => $assignee->name,
                        'email' => $assignee->email,
                    ],
                    $assignee instanceof Group => [
                        'type' => 'group',
                        'id' => $assignee->id,
                        'name' => $assignee->name,
                    ],
                    default => null,
                },
            ];
        })->values();

        $invitations = $campaign->invitations->map(fn ($invitation) => [
            'id' => $invitation->id,
            'role' => $invitation->role,
            'email' => $invitation->email,
            'group' => $invitation->group ? [
                'id' => $invitation->group->id,
                'name' => $invitation->group->name,
            ] : null,
            'expires_at' => optional($invitation->expires_at)->toAtomString(),
        ])->values();

        return Inertia::render('Campaigns/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'status' => $campaign->status,
                'synopsis' => $campaign->synopsis,
                'default_timezone' => $campaign->default_timezone,
                'start_date' => optional($campaign->start_date)->toDateString(),
                'end_date' => optional($campaign->end_date)->toDateString(),
                'turn_hours' => $campaign->turn_hours,
                'group' => [
                    'id' => $campaign->group->id,
                    'name' => $campaign->group->name,
                ],
                'region' => $campaign->region ? [
                    'id' => $campaign->region->id,
                    'name' => $campaign->region->name,
                ] : null,
                'members' => $members,
                'assignments' => $assignments,
                'invitations' => $invitations,
                'entities_count' => $entityCount,
                'recent_entities' => $recentEntities,
            ],
            'available_roles' => CampaignRoleAssignment::roles(),
            'available_statuses' => Campaign::statuses(),
        ]);
    }

    public function edit(Campaign $campaign): Response
    {
        $this->authorize('update', $campaign);

        $campaign->load('group.regions:id,group_id,name');

        $groups = collect([$campaign->group])->map(fn (Group $group) => [
            'id' => $group->id,
            'name' => $group->name,
            'regions' => $group->regions->map(fn (Region $region) => [
                'id' => $region->id,
                'name' => $region->name,
            ])->values(),
        ]);

        return Inertia::render('Campaigns/Edit', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'synopsis' => $campaign->synopsis,
                'status' => $campaign->status,
                'region_id' => $campaign->region_id,
                'default_timezone' => $campaign->default_timezone,
                'start_date' => optional($campaign->start_date)->toDateString(),
                'end_date' => optional($campaign->end_date)->toDateString(),
                'turn_hours' => $campaign->turn_hours,
            ],
            'groups' => $groups,
            'available_statuses' => Campaign::statuses(),
        ]);
    }

    public function update(CampaignUpdateRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $regionId = $request->integer('region_id');
        if ($regionId !== 0 && $regionId !== null) {
            $region = Region::query()
                ->where('group_id', $campaign->group_id)
                ->where('id', $regionId)
                ->first();

            if ($region === null) {
                throw ValidationException::withMessages([
                    'region_id' => 'Selected region must belong to the campaign group.',
                ]);
            }
        }

        $campaign->update([
            'title' => $request->string('title')->toString(),
            'synopsis' => $request->input('synopsis'),
            'status' => $request->string('status')->toString(),
            'region_id' => $request->integer('region_id') ?: null,
            'default_timezone' => $request->string('default_timezone')->toString(),
            'start_date' => $request->date('start_date'),
            'end_date' => $request->date('end_date'),
            'turn_hours' => $request->input('turn_hours'),
        ]);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()->route('campaigns.index')->with('success', 'Campaign archived.');
    }

    protected function assertUserManagesGroup(Authenticatable $user, Group $group): void
    {
        $isManager = $group->memberships()
            ->where('user_id', $user->getAuthIdentifier())
            ->whereIn('role', [
                GroupMembership::ROLE_OWNER,
                GroupMembership::ROLE_DUNGEON_MASTER,
            ])
            ->exists();

        if (! $isManager) {
            abort(403, 'You are not allowed to manage campaigns for this group.');
        }
    }

    protected function generateSlug(string $title): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'campaign';
        }

        $slug = $base;
        $counter = 1;

        while (Campaign::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
