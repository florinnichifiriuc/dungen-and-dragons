<?php

namespace App\Http\Controllers;

use App\Http\Requests\TileTemplateStoreRequest;
use App\Http\Requests\TileTemplateUpdateRequest;
use App\Models\Group;
use App\Models\TileTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class TileTemplateController extends Controller
{
    public function create(Group $group): Response
    {
        $this->authorize('create', [TileTemplate::class, $group]);

        return Inertia::render('TileTemplates/Create', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'worlds' => $group->worlds()->get(['id', 'name'])->map(fn ($world) => [
                'id' => $world->id,
                'name' => $world->name,
            ]),
        ]);
    }

    public function store(TileTemplateStoreRequest $request, Group $group): RedirectResponse
    {
        $validated = $request->validated();

        $edgeProfile = $this->decodeJsonField($validated['edge_profile'] ?? null);

        $imagePath = Arr::get($validated, 'image_path');

        if ($request->hasFile('image_upload')) {
            $imagePath = $request->file('image_upload')->store('tile-templates/'.$group->id, 'public');
        }

        $group->tileTemplates()->create([
            'world_id' => $validated['world_id'] ?? null,
            'created_by' => $request->user()?->getAuthIdentifier(),
            'key' => Arr::get($validated, 'key'),
            'name' => $validated['name'],
            'terrain_type' => $validated['terrain_type'],
            'movement_cost' => $validated['movement_cost'],
            'defense_bonus' => $validated['defense_bonus'],
            'image_path' => $imagePath,
            'edge_profile' => $edgeProfile,
        ]);

        return redirect()->route('groups.show', $group)->with('success', 'Tile template created.');
    }

    public function edit(Group $group, TileTemplate $tileTemplate): Response
    {
        $this->assertTemplateForGroup($group, $tileTemplate);
        $this->authorize('update', $tileTemplate);

        return Inertia::render('TileTemplates/Edit', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'worlds' => $group->worlds()->get(['id', 'name'])->map(fn ($world) => [
                'id' => $world->id,
                'name' => $world->name,
            ]),
            'template' => [
                'id' => $tileTemplate->id,
                'name' => $tileTemplate->name,
                'key' => $tileTemplate->key,
                'terrain_type' => $tileTemplate->terrain_type,
                'movement_cost' => $tileTemplate->movement_cost,
                'defense_bonus' => $tileTemplate->defense_bonus,
                'image_path' => $tileTemplate->image_path,
                'edge_profile' => $tileTemplate->edge_profile,
                'world_id' => $tileTemplate->world_id,
            ],
        ]);
    }

    public function update(TileTemplateUpdateRequest $request, Group $group, TileTemplate $tileTemplate): RedirectResponse
    {
        $this->assertTemplateForGroup($group, $tileTemplate);

        $validated = $request->validated();

        $edgeProfile = $this->decodeJsonField($validated['edge_profile'] ?? null);

        $imagePath = Arr::get($validated, 'image_path', $tileTemplate->image_path);

        if ($request->hasFile('image_upload')) {
            $imagePath = $request->file('image_upload')->store('tile-templates/'.$group->id, 'public');
        }

        $tileTemplate->update([
            'world_id' => $validated['world_id'] ?? null,
            'key' => Arr::get($validated, 'key'),
            'name' => $validated['name'],
            'terrain_type' => $validated['terrain_type'],
            'movement_cost' => $validated['movement_cost'],
            'defense_bonus' => $validated['defense_bonus'],
            'image_path' => $imagePath,
            'edge_profile' => $edgeProfile,
        ]);

        return redirect()->route('groups.show', $group)->with('success', 'Tile template updated.');
    }

    public function destroy(Group $group, TileTemplate $tileTemplate): RedirectResponse
    {
        $this->assertTemplateForGroup($group, $tileTemplate);
        $this->authorize('delete', $tileTemplate);

        abort_if($tileTemplate->mapTiles()->exists(), 422, 'Remove tiles that use this template before deleting it.');

        $tileTemplate->delete();

        return redirect()->route('groups.show', $group)->with('success', 'Tile template removed.');
    }

    protected function assertTemplateForGroup(Group $group, TileTemplate $template): void
    {
        abort_if($template->group_id !== $group->id, 404);
    }

    protected function decodeJsonField(?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
