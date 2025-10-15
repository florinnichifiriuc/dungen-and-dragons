<?php

use App\Models\AiRequest;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Region;
use App\Models\User;
use App\Models\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('assigns an ai delegate to a region and stores the directive', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $world = World::factory()->for($group)->create();
    $region = Region::factory()->for($group)->for($world)->create([
        'summary' => 'Forest of whispering oaks.',
    ]);

    Http::fake([
        '*/api/chat' => Http::response([
            'message' => [
                'content' => "Tone: warm guardian. Hooks: protect the refugees, bind the lurking shadow, celebrate the harvest moon.",
            ],
        ], 200),
    ]);

    $response = $this->actingAs($owner)->post(
        route('groups.regions.ai-delegate.store', [$group, $region]),
        [
            'focus' => 'Highlight local alliances.',
        ]
    );

    $response->assertRedirect(route('groups.show', $group));

    $region->refresh();

    expect($region->ai_controlled)->toBeTrue();
    expect($region->ai_delegate_summary)->toContain('Tone: warm guardian');
    expect($region->dungeon_master_id)->toBeNull();

    $this->assertDatabaseHas('ai_requests', [
        'request_type' => 'dm_takeover',
        'context_type' => Region::class,
        'context_id' => $region->id,
        'status' => AiRequest::STATUS_COMPLETED,
    ]);
});
