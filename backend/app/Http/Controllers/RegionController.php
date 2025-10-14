<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegionStoreRequest;
use App\Http\Requests\RegionUpdateRequest;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Region;
use App\Services\TurnScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RegionController extends Controller
{
    public function create(Group $group): Response
    {
        $this->authorize('create', [Region::class, $group]);

        return Inertia::render('Regions/Create', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'dungeonMasters' => $this->eligibleDungeonMasters($group),
        ]);
    }

    public function store(RegionStoreRequest $request, Group $group, TurnScheduler $scheduler): RedirectResponse
    {
        $this->authorize('create', [Region::class, $group]);

        $validated = $request->validated();
        $dungeonMasterId = $validated['dungeon_master_id'] ?? null;

        if ($dungeonMasterId !== null) {
            $this->assertDungeonMaster($group, $dungeonMasterId);
        }

        DB::transaction(function () use ($group, $validated, $dungeonMasterId, $scheduler): void {
            /** @var Region $region */
            $region = $group->regions()->create([
                'name' => $validated['name'],
                'summary' => $validated['summary'] ?? null,
                'description' => $validated['description'] ?? null,
                'dungeon_master_id' => $dungeonMasterId,
            ]);

            $scheduler->configure(
                $region,
                (int) $validated['turn_duration_hours'],
                isset($validated['next_turn_at']) ? CarbonImmutable::parse($validated['next_turn_at'])->utc() : null
            );
        });

        return redirect()->route('groups.show', $group)->with('success', 'Region created.');
    }

    public function show(Group $group, Region $region): RedirectResponse
    {
        $this->assertRegionForGroup($group, $region);
        $this->authorize('view', $region);

        return redirect()->route('groups.show', $group)->withFragment('region-'.$region->id);
    }

    public function edit(Group $group, Region $region): Response
    {
        $this->assertRegionForGroup($group, $region);
        $this->authorize('update', $region);

        $region->loadMissing('turnConfiguration');

        return Inertia::render('Regions/Edit', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'region' => [
                'id' => $region->id,
                'name' => $region->name,
                'summary' => $region->summary,
                'description' => $region->description,
                'dungeon_master_id' => $region->dungeon_master_id,
                'turn_duration_hours' => $region->turnConfiguration?->turn_duration_hours,
                'next_turn_at' => optional($region->turnConfiguration?->next_turn_at)->toAtomString(),
            ],
            'dungeonMasters' => $this->eligibleDungeonMasters($group),
        ]);
    }

    public function update(RegionUpdateRequest $request, Group $group, Region $region, TurnScheduler $scheduler): RedirectResponse
    {
        $this->assertRegionForGroup($group, $region);
        $this->authorize('update', $region);

        $validated = $request->validated();
        $dungeonMasterId = $validated['dungeon_master_id'] ?? null;

        if ($dungeonMasterId !== null) {
            $this->assertDungeonMaster($group, $dungeonMasterId);
        }

        DB::transaction(function () use ($region, $validated, $dungeonMasterId, $scheduler): void {
            $region->update([
                'name' => $validated['name'],
                'summary' => $validated['summary'] ?? null,
                'description' => $validated['description'] ?? null,
                'dungeon_master_id' => $dungeonMasterId,
            ]);

            $scheduler->configure(
                $region,
                (int) $validated['turn_duration_hours'],
                isset($validated['next_turn_at']) ? CarbonImmutable::parse($validated['next_turn_at'])->utc() : null
            );
        });

        return redirect()->route('groups.show', $group)->with('success', 'Region updated.');
    }

    public function destroy(Group $group, Region $region): RedirectResponse
    {
        $this->assertRegionForGroup($group, $region);
        $this->authorize('delete', $region);

        $region->delete();

        return redirect()->route('groups.show', $group)->with('success', 'Region removed.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function eligibleDungeonMasters(Group $group): array
    {
        return $group->memberships()
            ->whereIn('role', [
                GroupMembership::ROLE_OWNER,
                GroupMembership::ROLE_DUNGEON_MASTER,
            ])
            ->with('user:id,name')
            ->get()
            ->map(fn (GroupMembership $membership) => [
                'id' => $membership->user->id,
                'name' => $membership->user->name,
                'role' => $membership->role,
            ])
            ->values()
            ->all();
    }

    protected function assertDungeonMaster(Group $group, int $userId): void
    {
        $allowed = $group->memberships()
            ->where('user_id', $userId)
            ->whereIn('role', [
                GroupMembership::ROLE_OWNER,
                GroupMembership::ROLE_DUNGEON_MASTER,
            ])
            ->exists();

        abort_unless($allowed, 422, 'Selected dungeon master must belong to the group as a GM or DM.');
    }

    protected function assertRegionForGroup(Group $group, Region $region): void
    {
        abort_if($region->group_id !== $group->id, 404);
    }
}
