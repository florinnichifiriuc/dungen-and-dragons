<?php

use App\Models\AiRequest;
use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns an ai-crafted npc response for a session', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $campaign = Campaign::factory()->for($group)->create([
        'created_by' => $user->id,
        'title' => 'Shadows of Ithilien',
    ]);

    $session = CampaignSession::factory()->for($campaign)->create([
        'title' => 'Council at Dawnspire',
        'agenda' => 'Negotiate a truce with the skybound enclave.',
    ]);

    Http::fake([
        '*/api/chat' => Http::response([
            'message' => [
                'content' => '[Captain Mirela] "Hold fast, friends. The winds favor honest hearts tonight."',
            ],
        ], 200),
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson(
        route('api.campaigns.sessions.npc-dialogue', [$campaign, $session]),
        [
            'npc_name' => 'Captain Mirela',
            'prompt' => 'The players ask for safe passage to the vault.',
            'tone' => 'steadfast',
        ]
    );

    $response
        ->assertOk()
        ->assertJsonFragment([
            'reply' => '[Captain Mirela] "Hold fast, friends. The winds favor honest hearts tonight."',
            'status' => AiRequest::STATUS_COMPLETED,
        ]);

    $this->assertDatabaseHas('ai_requests', [
        'request_type' => 'npc_dialogue',
        'context_type' => CampaignSession::class,
        'context_id' => $session->id,
        'status' => AiRequest::STATUS_COMPLETED,
    ]);
});
