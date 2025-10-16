<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnalyticsEventRequest;
use App\Models\Group;
use App\Services\AnalyticsRecorder;
use Illuminate\Http\JsonResponse;

class AnalyticsEventController extends Controller
{
    public function __construct(private readonly AnalyticsRecorder $analytics)
    {
    }

    public function store(StoreAnalyticsEventRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $group = null;

        if (array_key_exists('group_id', $validated) && $validated['group_id'] !== null) {
            /** @var Group $group */
            $group = Group::query()->findOrFail($validated['group_id']);
            $this->authorize('view', $group);
        }

        $payload = $validated['payload'] ?? [];
        $payload['group_id'] = $group?->id;

        $this->analytics->record(
            key: $validated['key'],
            payload: $payload,
            actor: $request->user(),
            group: $group,
        );

        return response()->json(['status' => 'ok']);
    }
}
