<?php

use App\Events\ConditionTimerSummaryBroadcasted;
use App\Models\Group;
use App\Models\Map;
use App\Models\MapToken;
use App\Services\ConditionTimerSummaryProjector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

it('projects redacted summaries with caching telemetry', function () {
    $group = Group::factory()->create();
    $map = Map::factory()->for($group)->create();

    $visible = MapToken::factory()->for($map)->create([
        'name' => 'Stormguard',
        'hidden' => false,
        'faction' => MapToken::FACTION_ALLIED,
        'status_conditions' => ['restrained'],
        'status_condition_durations' => ['restrained' => 3],
    ]);

    MapToken::factory()->for($map)->create([
        'name' => 'Assassin',
        'hidden' => true,
        'faction' => MapToken::FACTION_HOSTILE,
        'status_conditions' => ['poisoned'],
        'status_condition_durations' => ['poisoned' => 2],
    ]);

    Cache::flush();
    Log::spy();

    $projector = app(ConditionTimerSummaryProjector::class);

    $summary = $projector->projectForGroup($group);

    expect($summary['group_id'])->toBe($group->id);
    expect($summary['entries'])->toHaveCount(2);

    $allyEntry = collect($summary['entries'])->first(fn ($entry) => $entry['token']['id'] === $visible->id);
    expect($allyEntry['token']['visibility'])->toBe('visible');
    expect($allyEntry['token']['disposition'])->toBe('ally');
    expect($allyEntry['conditions'][0]['rounds'])->toBe(3);
    expect($allyEntry['conditions'][0]['rounds_hint'])->toBeNull();

    $hiddenEntry = collect($summary['entries'])->first(fn ($entry) => $entry['token']['id'] !== $visible->id);
    expect($hiddenEntry['token']['visibility'])->toBe('obscured');
    expect($hiddenEntry['token']['label'])->toBe('Shrouded presence');
    expect($hiddenEntry['conditions'][0]['rounds'])->toBeNull();

    Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($group) {
        return $message === 'condition_timer_summary_cache_miss' && $context['group_id'] === $group->id;
    })->once();

    Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($group) {
        return $message === 'condition_timer_summary_cache_rebuilt'
            && $context['group_id'] === $group->id
            && $context['entries'] === 2;
    })->once();

    Log::spy();

    $projector->projectForGroup($group);

    Log::shouldNotHaveReceived('info');
});

it('refreshes group summaries and broadcasts payloads', function () {
    $group = Group::factory()->create();
    $map = Map::factory()->for($group)->create();

    MapToken::factory()->for($map)->create([
        'name' => 'Veilrunner',
        'hidden' => false,
        'faction' => MapToken::FACTION_NEUTRAL,
        'status_conditions' => ['frightened'],
        'status_condition_durations' => ['frightened' => 1],
    ]);

    Cache::flush();
    Event::fake([ConditionTimerSummaryBroadcasted::class]);

    $projector = app(ConditionTimerSummaryProjector::class);

    $summary = $projector->refreshForGroup($group);

    expect($summary['entries'])->not->toBeEmpty();

    Event::assertDispatched(ConditionTimerSummaryBroadcasted::class, function (ConditionTimerSummaryBroadcasted $event) use ($group) {
        return $event->groupId === $group->id && $event->summary['group_id'] === $group->id;
    });
});
