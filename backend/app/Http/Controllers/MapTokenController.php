<?php

namespace App\Http\Controllers;

use App\Events\MapTokenBroadcasted;
use App\Http\Requests\MapTokenStoreRequest;
use App\Http\Requests\MapTokenUpdateRequest;
use App\Models\Group;
use App\Models\Map;
use App\Models\MapToken;
use App\Support\Broadcasting\MapTokenPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class MapTokenController extends Controller
{
    public function store(MapTokenStoreRequest $request, Group $group, Map $map): RedirectResponse
    {
        $this->assertMapForGroup($group, $map);

        $validated = $request->validated();

        $token = $map->tokens()->create([
            'name' => $validated['name'],
            'x' => $validated['x'],
            'y' => $validated['y'],
            'color' => $this->normalizeOptionalString($validated['color'] ?? null),
            'size' => $validated['size'] ?? 'medium',
            'faction' => $validated['faction'] ?? MapToken::FACTION_NEUTRAL,
            'initiative' => $this->normalizeOptionalInteger($validated['initiative'] ?? null),
            'status_effects' => $this->normalizeOptionalString($validated['status_effects'] ?? null),
            'hit_points' => $this->normalizeOptionalInteger($validated['hit_points'] ?? null),
            'temporary_hit_points' => $this->normalizeOptionalInteger($validated['temporary_hit_points'] ?? null),
            'max_hit_points' => $this->normalizeOptionalInteger($validated['max_hit_points'] ?? null),
            'z_index' => $this->normalizeOptionalInteger($validated['z_index'] ?? 0) ?? 0,
            'hidden' => (bool) Arr::get($validated, 'hidden', false),
            'gm_note' => $this->normalizeOptionalString($validated['gm_note'] ?? null),
        ]);

        event(new MapTokenBroadcasted($map, 'created', MapTokenPayload::from($token)));

        return redirect()->route('groups.maps.show', [$group, $map])->with('success', 'Token placed.');
    }

    public function update(MapTokenUpdateRequest $request, Group $group, Map $map, MapToken $token): RedirectResponse
    {
        $this->assertMapForGroup($group, $map);
        $this->assertTokenForMap($map, $token);

        $validated = $request->validated();

        $data = [];

        if (array_key_exists('name', $validated)) {
            $data['name'] = $validated['name'];
        }

        if (array_key_exists('x', $validated)) {
            $data['x'] = $validated['x'];
        }

        if (array_key_exists('y', $validated)) {
            $data['y'] = $validated['y'];
        }

        if (array_key_exists('color', $validated)) {
            $data['color'] = $this->normalizeOptionalString($validated['color']);
        }

        if (array_key_exists('size', $validated)) {
            $data['size'] = $validated['size'] ?? 'medium';
        }

        if (array_key_exists('faction', $validated)) {
            $data['faction'] = $validated['faction'] ?? MapToken::FACTION_NEUTRAL;
        }

        if (array_key_exists('initiative', $validated)) {
            $data['initiative'] = $this->normalizeOptionalInteger($validated['initiative']);
        }

        if (array_key_exists('status_effects', $validated)) {
            $data['status_effects'] = $this->normalizeOptionalString($validated['status_effects']);
        }

        if (array_key_exists('hit_points', $validated)) {
            $data['hit_points'] = $this->normalizeOptionalInteger($validated['hit_points']);
        }

        if (array_key_exists('temporary_hit_points', $validated)) {
            $data['temporary_hit_points'] = $this->normalizeOptionalInteger($validated['temporary_hit_points']);
        }

        if (array_key_exists('max_hit_points', $validated)) {
            $data['max_hit_points'] = $this->normalizeOptionalInteger($validated['max_hit_points']);
        }

        if (array_key_exists('z_index', $validated)) {
            $data['z_index'] = $this->normalizeOptionalInteger($validated['z_index']) ?? 0;
        }

        if (array_key_exists('hidden', $validated)) {
            $data['hidden'] = (bool) $validated['hidden'];
        }

        if (array_key_exists('gm_note', $validated)) {
            $data['gm_note'] = $this->normalizeOptionalString($validated['gm_note']);
        }

        if (! empty($data)) {
            $token->update($data);
        }

        event(new MapTokenBroadcasted($map, 'updated', MapTokenPayload::from($token->fresh())));

        return redirect()->route('groups.maps.show', [$group, $map])->with('success', 'Token updated.');
    }

    public function destroy(Group $group, Map $map, MapToken $token): RedirectResponse
    {
        $this->assertMapForGroup($group, $map);
        $this->assertTokenForMap($map, $token);
        $this->authorize('delete', $token);

        $tokenId = (int) $token->id;

        $token->delete();

        event(new MapTokenBroadcasted($map, 'deleted', [
            'id' => $tokenId,
        ]));

        return redirect()->route('groups.maps.show', [$group, $map])->with('success', 'Token removed.');
    }

    protected function assertMapForGroup(Group $group, Map $map): void
    {
        abort_if($map->group_id !== $group->id, 404);
    }

    protected function assertTokenForMap(Map $map, MapToken $token): void
    {
        abort_if($token->map_id !== $map->id, 404);
    }

    protected function normalizeOptionalString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function normalizeOptionalInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
