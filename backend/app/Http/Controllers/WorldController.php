<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorldStoreRequest;
use App\Http\Requests\WorldUpdateRequest;
use App\Models\Group;
use App\Models\World;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorldController extends Controller
{
    public function create(Group $group): Response
    {
        $this->authorize('create', [World::class, $group]);

        return Inertia::render('Worlds/Create', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'defaults' => [
                'default_turn_duration_hours' => 24,
            ],
        ]);
    }

    public function store(WorldStoreRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('create', [World::class, $group]);

        $validated = $request->validated();

        $group->worlds()->create([
            'name' => $validated['name'],
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'default_turn_duration_hours' => $validated['default_turn_duration_hours'],
        ]);

        return redirect()->route('groups.show', $group)->with('success', 'World created.');
    }

    public function edit(Group $group, World $world): Response
    {
        $this->assertWorldForGroup($group, $world);
        $this->authorize('update', $world);

        return Inertia::render('Worlds/Edit', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'world' => [
                'id' => $world->id,
                'name' => $world->name,
                'summary' => $world->summary,
                'description' => $world->description,
                'default_turn_duration_hours' => $world->default_turn_duration_hours,
            ],
        ]);
    }

    public function update(WorldUpdateRequest $request, Group $group, World $world): RedirectResponse
    {
        $this->assertWorldForGroup($group, $world);
        $this->authorize('update', $world);

        $world->update($request->validated());

        return redirect()->route('groups.show', $group)->with('success', 'World updated.');
    }

    public function destroy(Group $group, World $world): RedirectResponse
    {
        $this->assertWorldForGroup($group, $world);
        $this->authorize('delete', $world);

        abort_if($world->regions()->exists(), 422, 'Remove or reassign regions before deleting this world.');

        $world->delete();

        return redirect()->route('groups.show', $group)->with('success', 'World removed.');
    }

    protected function assertWorldForGroup(Group $group, World $world): void
    {
        abort_if($world->group_id !== $group->id, 404);
    }
}
