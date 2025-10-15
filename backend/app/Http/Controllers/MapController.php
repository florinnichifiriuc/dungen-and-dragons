<?php

namespace App\Http\Controllers;

use App\Http\Requests\MapFogUpdateRequest;
use App\Http\Requests\MapStoreRequest;
use App\Http\Requests\MapUpdateRequest;
use App\Models\Group;
use App\Models\Map;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function create(Group $group): Response
    {
        $this->authorize('create', [Map::class, $group]);

        return Inertia::render('Maps/Create', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'regions' => $group->regions()->get(['id', 'name'])->map(fn ($region) => [
                'id' => $region->id,
                'name' => $region->name,
            ]),
            'defaults' => [
                'base_layer' => 'hex',
                'orientation' => 'pointy',
            ],
        ]);
    }

    public function store(MapStoreRequest $request, Group $group): RedirectResponse
    {
        $validated = $request->validated();

        $fogData = $this->decodeJsonField($validated['fog_data'] ?? null);

        $group->maps()->create([
            'region_id' => $validated['region_id'] ?? null,
            'title' => $validated['title'],
            'base_layer' => $validated['base_layer'],
            'orientation' => $validated['orientation'],
            'width' => Arr::get($validated, 'width'),
            'height' => Arr::get($validated, 'height'),
            'gm_only' => (bool) Arr::get($validated, 'gm_only', false),
            'fog_data' => $fogData,
        ]);

        return redirect()->route('groups.show', $group)->with('success', 'Map created.');
    }

    public function show(Group $group, Map $map): Response
    {
        $this->assertMapForGroup($group, $map);
        $this->authorize('view', $map);

        $map->load([
            'tiles.tileTemplate:id,name,terrain_type,movement_cost,defense_bonus',
            'tokens:id,map_id,name,x,y,color,size,faction,initiative,status_effects,hit_points,temporary_hit_points,max_hit_points,z_index,hidden,gm_note',
        ]);

        return Inertia::render('Maps/Show', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'map' => [
                'id' => $map->id,
                'title' => $map->title,
                'base_layer' => $map->base_layer,
                'orientation' => $map->orientation,
                'width' => $map->width,
                'height' => $map->height,
                'gm_only' => $map->gm_only,
                'region' => $map->region ? [
                    'id' => $map->region->id,
                    'name' => $map->region->name,
                ] : null,
                'fog' => [
                    'hidden_tile_ids' => array_values(array_unique(Arr::get($map->fog_data ?? [], 'hidden_tile_ids', []))),
                ],
            ],
            'tiles' => $map->tiles
                ->sortBy(fn ($tile) => [$tile->q, $tile->r])
                ->map(fn ($tile) => [
                    'id' => $tile->id,
                    'q' => $tile->q,
                    'r' => $tile->r,
                    'elevation' => $tile->elevation,
                    'locked' => $tile->locked,
                    'variant' => $tile->variant,
                    'template' => [
                        'id' => $tile->tileTemplate->id,
                        'name' => $tile->tileTemplate->name,
                        'terrain_type' => $tile->tileTemplate->terrain_type,
                        'movement_cost' => $tile->tileTemplate->movement_cost,
                        'defense_bonus' => $tile->tileTemplate->defense_bonus,
                    ],
                ])->values(),
            'tokens' => $map->tokens
                ->sortBy([
                    ['initiative', 'desc'],
                    ['z_index', 'desc'],
                    ['name', 'asc'],
                    ['id', 'asc'],
                ])
                ->map(fn ($token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'x' => $token->x,
                    'y' => $token->y,
                    'color' => $token->color,
                    'size' => $token->size,
                    'faction' => $token->faction,
                    'initiative' => $token->initiative,
                    'status_effects' => $token->status_effects,
                    'hit_points' => $token->hit_points,
                    'temporary_hit_points' => $token->temporary_hit_points,
                    'max_hit_points' => $token->max_hit_points,
                    'z_index' => $token->z_index,
                    'hidden' => (bool) $token->hidden,
                    'gm_note' => $token->gm_note,
                ])->values(),
            'tile_templates' => $group->tileTemplates()
                ->orderBy('name')
                ->get(['id', 'name', 'terrain_type', 'movement_cost', 'defense_bonus'])
                ->map(fn ($template) => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'terrain_type' => $template->terrain_type,
                    'movement_cost' => $template->movement_cost,
                    'defense_bonus' => $template->defense_bonus,
                ]),
        ]);
    }

    public function edit(Group $group, Map $map): Response
    {
        $this->assertMapForGroup($group, $map);
        $this->authorize('update', $map);

        return Inertia::render('Maps/Edit', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'map' => [
                'id' => $map->id,
                'title' => $map->title,
                'base_layer' => $map->base_layer,
                'orientation' => $map->orientation,
                'width' => $map->width,
                'height' => $map->height,
                'gm_only' => $map->gm_only,
                'region_id' => $map->region_id,
                'fog_data' => $map->fog_data,
            ],
            'regions' => $group->regions()->get(['id', 'name'])->map(fn ($region) => [
                'id' => $region->id,
                'name' => $region->name,
            ]),
        ]);
    }

    public function update(MapUpdateRequest $request, Group $group, Map $map): RedirectResponse
    {
        $this->assertMapForGroup($group, $map);

        $validated = $request->validated();

        $fogData = $this->decodeJsonField($validated['fog_data'] ?? null);

        $map->update([
            'region_id' => $validated['region_id'] ?? null,
            'title' => $validated['title'],
            'base_layer' => $validated['base_layer'],
            'orientation' => $validated['orientation'],
            'width' => Arr::get($validated, 'width'),
            'height' => Arr::get($validated, 'height'),
            'gm_only' => (bool) Arr::get($validated, 'gm_only', false),
            'fog_data' => $fogData,
        ]);

        return redirect()->route('groups.maps.show', [$group, $map])->with('success', 'Map updated.');
    }

    public function updateFog(MapFogUpdateRequest $request, Group $group, Map $map): RedirectResponse
    {
        $this->assertMapForGroup($group, $map);

        $hiddenTileIds = collect($request->validated('hidden_tile_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($hiddenTileIds === []) {
            $fogData = Arr::except($map->fog_data ?? [], ['hidden_tile_ids']);
        } else {
            $fogData = array_merge($map->fog_data ?? [], [
                'hidden_tile_ids' => $hiddenTileIds,
            ]);
        }

        $map->update([
            'fog_data' => empty($fogData) ? null : $fogData,
        ]);

        return redirect()->route('groups.maps.show', [$group, $map])->with('success', 'Fog of war updated.');
    }

    public function destroy(Group $group, Map $map): RedirectResponse
    {
        $this->assertMapForGroup($group, $map);
        $this->authorize('delete', $map);

        $map->delete();

        return redirect()->route('groups.show', $group)->with('success', 'Map removed.');
    }

    protected function assertMapForGroup(Group $group, Map $map): void
    {
        abort_if($map->group_id !== $group->id, 404);
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
