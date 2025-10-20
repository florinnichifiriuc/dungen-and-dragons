<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AiIdeaRequest;
use App\Models\Group;
use App\Models\Map;
use App\Services\AiContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class GroupMapSeedIdeaController extends Controller
{
    public function __invoke(AiIdeaRequest $request, Group $group, AiContentService $ai): JsonResponse
    {
        Gate::authorize('create', [Map::class, $group]);

        $context = (array) $request->input('context', []);
        $map = $this->hydrateVirtualMap($group, $context);

        $result = $ai->draftMapPlan($map, (string) $request->input('prompt', ''), $request->user());

        return response()->json([
            'idea' => $result['summary'],
            'structured' => [
                'summary' => $result['summary'],
                'fields' => $result['fields'],
                'tips' => $result['tips'],
                'image_prompt' => $result['image_prompt'],
            ],
        ]);
    }

    protected function hydrateVirtualMap(Group $group, array $context): Map
    {
        $map = Map::make([
            'title' => (string) ($context['title'] ?? 'New region map'),
            'base_layer' => (string) ($context['base_layer'] ?? 'hex'),
            'orientation' => (string) ($context['orientation'] ?? 'pointy'),
            'width' => $this->toNullableInt($context['width'] ?? null),
            'height' => $this->toNullableInt($context['height'] ?? null),
            'gm_only' => false,
            'fog_data' => $this->decodeFogData($context['fog_data'] ?? null),
        ]);

        $map->setRelation('group', $group);

        $regionId = $this->toNullableInt($context['region_id'] ?? null);
        if ($regionId) {
            $region = $group->regions()->firstWhere('id', $regionId);
            if ($region) {
                $map->setRelation('region', $region);
            }
        }

        return $map;
    }

    protected function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    protected function decodeFogData(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
