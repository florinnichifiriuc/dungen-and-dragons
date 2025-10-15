<?php

namespace App\Http\Controllers;

use App\Events\MapTileBroadcasted;
use App\Http\Requests\MapTileStoreRequest;
use App\Http\Requests\MapTileUpdateRequest;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapTile;
use App\Models\TileTemplate;
use App\Support\Broadcasting\MapTilePayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class MapTileController extends Controller
{
    public function store(MapTileStoreRequest $request, Group $group, Map $map): RedirectResponse
    {
        $this->assertMapForGroup($group, $map);

        $validated = $request->validated();

        /** @var TileTemplate $template */
        $template = TileTemplate::findOrFail($validated['tile_template_id']);

        abort_if($template->group_id !== $group->id, 404);
        abort_if($map->tiles()->where('q', $validated['q'])->where('r', $validated['r'])->exists(), 422, 'A tile already occupies that coordinate.');

        $variant = $this->decodeJsonField($validated['variant'] ?? null);

        $tile = $map->tiles()->create([
            'tile_template_id' => $template->id,
            'q' => $validated['q'],
            'r' => $validated['r'],
            'orientation' => $map->orientation,
            'elevation' => Arr::get($validated, 'elevation', 0),
            'variant' => $variant,
            'locked' => (bool) Arr::get($validated, 'locked', false),
        ]);

        event(new MapTileBroadcasted($map, 'created', MapTilePayload::from($tile)));

        return redirect()->route('groups.maps.show', [$group, $map])->with('success', 'Tile placed.');
    }

    public function update(MapTileUpdateRequest $request, Group $group, Map $map, MapTile $tile): RedirectResponse
    {
        $this->assertMapForGroup($group, $map);
        $this->assertTileForMap($map, $tile);

        $membership = $group->memberships()
            ->where('user_id', $request->user()?->getAuthIdentifier())
            ->first();

        abort_if($membership === null || ! in_array($membership->role, [
            GroupMembership::ROLE_OWNER,
            GroupMembership::ROLE_DUNGEON_MASTER,
        ], true), 403);

        if ($tile->locked && $membership->role !== GroupMembership::ROLE_OWNER) {
            abort(403, 'Tile is locked.');
        }

        $validated = $request->validated();

        $data = [];

        if (isset($validated['tile_template_id'])) {
            $template = TileTemplate::findOrFail($validated['tile_template_id']);
            abort_if($template->group_id !== $group->id, 404);
            $data['tile_template_id'] = $template->id;
        }

        $q = $validated['q'] ?? $tile->q;
        $r = $validated['r'] ?? $tile->r;

        if ($q !== $tile->q || $r !== $tile->r) {
            abort_if($map->tiles()->where('q', $q)->where('r', $r)->where('id', '!=', $tile->id)->exists(), 422, 'A tile already occupies that coordinate.');
            $data['q'] = $q;
            $data['r'] = $r;
        }

        if (array_key_exists('elevation', $validated)) {
            $data['elevation'] = $validated['elevation'];
        }

        if (array_key_exists('locked', $validated)) {
            $data['locked'] = (bool) $validated['locked'];
        }

        if (array_key_exists('variant', $validated)) {
            $data['variant'] = $this->decodeJsonField($validated['variant']);
        }

        if (! empty($data)) {
            $tile->update($data);
        }

        event(new MapTileBroadcasted($map, 'updated', MapTilePayload::from($tile->fresh())));

        return redirect()->route('groups.maps.show', [$group, $map])->with('success', 'Tile updated.');
    }

    public function destroy(Group $group, Map $map, MapTile $tile): RedirectResponse
    {
        $this->assertMapForGroup($group, $map);
        $this->assertTileForMap($map, $tile);

        $this->authorize('delete', $tile);

        $tileId = (int) $tile->id;

        $tile->delete();

        event(new MapTileBroadcasted($map, 'deleted', [
            'id' => $tileId,
        ]));

        return redirect()->route('groups.maps.show', [$group, $map])->with('success', 'Tile removed.');
    }

    protected function assertMapForGroup(Group $group, Map $map): void
    {
        abort_if($map->group_id !== $group->id, 404);
    }

    protected function assertTileForMap(Map $map, MapTile $tile): void
    {
        abort_if($tile->map_id !== $map->id, 404);
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
