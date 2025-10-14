<?php

namespace App\Http\Controllers;

use App\Http\Requests\TurnProcessRequest;
use App\Models\Group;
use App\Models\Region;
use App\Services\TurnScheduler;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegionTurnController extends Controller
{
    public function create(Group $group, Region $region): Response
    {
        $this->assertRegionForGroup($group, $region);
        $region->loadMissing([
            'turnConfiguration',
            'turns.processedBy:id,name',
        ]);

        $configuration = $region->turnConfiguration;
        abort_unless($configuration, 404, 'Region has no turn schedule.');

        $this->authorize('update', $configuration);

        return Inertia::render('Regions/ProcessTurn', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
            ],
            'region' => [
                'id' => $region->id,
                'name' => $region->name,
                'summary' => $region->summary,
                'turn_configuration' => [
                    'turn_duration_hours' => $configuration->turn_duration_hours,
                    'next_turn_at' => optional($configuration->next_turn_at)->toAtomString(),
                    'last_processed_at' => optional($configuration->last_processed_at)->toAtomString(),
                    'is_due' => $configuration->isDue(),
                ],
                'recent_turns' => $region->turns
                    ->sortByDesc('number')
                    ->take(5)
                    ->map(fn ($turn) => [
                        'id' => $turn->id,
                        'number' => $turn->number,
                        'processed_at' => optional($turn->processed_at)->toAtomString(),
                        'summary' => $turn->summary,
                        'used_ai_fallback' => $turn->used_ai_fallback,
                        'processed_by' => $turn->processedBy ? [
                            'id' => $turn->processedBy->id,
                            'name' => $turn->processedBy->name,
                        ] : null,
                    ])->values(),
            ],
        ]);
    }

    public function store(TurnProcessRequest $request, Group $group, Region $region, TurnScheduler $scheduler): RedirectResponse
    {
        $this->assertRegionForGroup($group, $region);
        $region->loadMissing('turnConfiguration');

        $configuration = $region->turnConfiguration;
        abort_unless($configuration, 422, 'Region is missing a turn configuration.');

        $this->authorize('update', $configuration);

        $turn = $scheduler->process(
            $region,
            $request->user(),
            $request->input('summary'),
            $request->boolean('use_ai_fallback')
        );

        return redirect()
            ->route('groups.show', $group)
            ->with('success', sprintf('Turn #%d for %s processed.', $turn->number, $region->name));
    }

    protected function assertRegionForGroup(Group $group, Region $region): void
    {
        abort_if($region->group_id !== $group->id, 404);
    }
}
