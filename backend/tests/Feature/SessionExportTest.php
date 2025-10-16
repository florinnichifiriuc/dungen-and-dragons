<?php

use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\SessionNote;
use App\Models\SessionRecap;
use App\Models\SessionReward;
use App\Models\User;
use App\Services\ConditionTimerAcknowledgementService;
use App\Services\ConditionTimerChronicleService;
use App\Services\ConditionTimerSummaryProjector;
use App\Services\ConditionTimerSummaryShareService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function seedCampaignSession(): array
{
    $manager = User::factory()->create();
    $group = Group::factory()->for($manager, 'creator')->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $campaign = Campaign::factory()->for($group)->create([
        'created_by' => $manager->id,
    ]);

    $session = CampaignSession::factory()->for($campaign)->create([
        'created_by' => $manager->id,
        'title' => 'Vault of echoes',
    ]);

    return [$manager, $campaign, $group, $session];
}

it('includes gm notes for managers and hides them from players in markdown export', function () {
    [$manager, $campaign, $group, $session] = seedCampaignSession();

    $gmNote = SessionNote::create([
        'campaign_id' => $campaign->id,
        'campaign_session_id' => $session->id,
        'author_id' => $manager->id,
        'visibility' => SessionNote::VISIBILITY_GM,
        'content' => 'Secret treaty details.',
    ]);

    SessionNote::create([
        'campaign_id' => $campaign->id,
        'campaign_session_id' => $session->id,
        'author_id' => $manager->id,
        'visibility' => SessionNote::VISIBILITY_PLAYERS,
        'content' => 'Public celebration log.',
    ]);

    $managerResponse = $this->actingAs($manager)->get(route('campaigns.sessions.exports.markdown', [
        'campaign' => $campaign,
        'session' => $session,
    ]));

    $managerResponse->assertOk();
    expect($managerResponse->headers->get('content-type'))->toContain('text/markdown');
    expect($managerResponse->getContent())->toContain('Secret treaty details.');

    $player = User::factory()->create();
    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $playerResponse = $this->actingAs($player)->get(route('campaigns.sessions.exports.markdown', [
        'campaign' => $campaign,
        'session' => $session,
    ]));

    $playerResponse->assertOk();
    expect($playerResponse->getContent())->not->toContain($gmNote->content);
    expect($playerResponse->getContent())->toContain('Public celebration log.');
});

it('streams pdf exports for any authorized viewer', function () {
    [$manager, $campaign, , $session] = seedCampaignSession();

    $response = $this->actingAs($manager)->get(route('campaigns.sessions.exports.pdf', [
        'campaign' => $campaign,
        'session' => $session,
    ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment; filename="session-');
});

it('allows managers to upload and remove stored recordings while blocking players', function () {
    [$manager, $campaign, $group, $session] = seedCampaignSession();
    Storage::fake('public');

    $upload = UploadedFile::fake()->create('recap.mp3', 1000, 'audio/mpeg');

    $this->actingAs($manager)
        ->post(route('campaigns.sessions.recording.store', ['campaign' => $campaign, 'session' => $session]), [
            'recording' => $upload,
        ])
        ->assertRedirect(route('campaigns.sessions.show', [$campaign, $session]));

    $session->refresh();
    $storedPath = $session->recording_path;
    expect($storedPath)->not->toBeNull();
    Storage::disk('public')->assertExists($storedPath);

    $player = User::factory()->create();
    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $this->actingAs($player)
        ->post(route('campaigns.sessions.recording.store', ['campaign' => $campaign, 'session' => $session]), [
            'recording' => UploadedFile::fake()->create('blocked.mp3', 500, 'audio/mpeg'),
        ])
        ->assertForbidden();

    $this->actingAs($manager)
        ->delete(route('campaigns.sessions.recording.destroy', ['campaign' => $campaign, 'session' => $session]))
        ->assertRedirect(route('campaigns.sessions.show', [$campaign, $session]));

    $session->refresh();
    expect($session->recording_path)->toBeNull();
    Storage::disk('public')->assertMissing($storedPath);
});

it('includes session recaps in markdown exports for all viewers', function () {
    [$manager, $campaign, $group, $session] = seedCampaignSession();

    $player = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    SessionRecap::create([
        'campaign_id' => $campaign->id,
        'campaign_session_id' => $session->id,
        'author_id' => $manager->id,
        'title' => 'GM Chronicle',
        'body' => 'The party sealed the breach with starlight wards.',
    ]);

    SessionRecap::create([
        'campaign_id' => $campaign->id,
        'campaign_session_id' => $session->id,
        'author_id' => $player->id,
        'title' => 'Player Notes',
        'body' => 'We owe the artificer three favors after tonight.',
    ]);

    $managerExport = $this->actingAs($manager)->get(route('campaigns.sessions.exports.markdown', [
        'campaign' => $campaign,
        'session' => $session,
    ]));

    $managerExport->assertOk();
    expect($managerExport->getContent())
        ->toContain('GM Chronicle')
        ->toContain('Player Notes')
        ->toContain('starlight wards')
        ->toContain('owe the artificer');

    $playerExport = $this->actingAs($player)->get(route('campaigns.sessions.exports.markdown', [
        'campaign' => $campaign,
        'session' => $session,
    ]));

    $playerExport->assertOk();
    expect($playerExport->getContent())
        ->toContain('GM Chronicle')
        ->toContain('Player Notes');
});

it('includes condition timer outlook and chronicle in markdown exports', function () {
    [$manager, $campaign, $group, $session] = seedCampaignSession();

    $map = Map::factory()->for($group)->create([
        'title' => 'Vault Approach',
    ]);

    $token = MapToken::factory()->for($map)->create([
        'name' => 'Specter Vanguard',
        'faction' => MapToken::FACTION_ALLIED,
        'hidden' => false,
        'status_conditions' => ['blinded'],
        'status_condition_durations' => ['blinded' => 3],
    ]);

    /** @var ConditionTimerChronicleService $chronicle */
    $chronicle = app(ConditionTimerChronicleService::class);
    $chronicle->recordAdjustments($group, $token, [
        ['condition' => 'blinded', 'previous' => null, 'next' => 3],
    ], 'token_created', $manager);

    /** @var ConditionTimerSummaryProjector $projector */
    $projector = app(ConditionTimerSummaryProjector::class);
    $summary = $projector->refreshForGroup($group, trigger: 'test', broadcast: false);

    /** @var ConditionTimerAcknowledgementService $acknowledgements */
    $acknowledgements = app(ConditionTimerAcknowledgementService::class);
    $acknowledgements->recordAcknowledgement(
        $group,
        $token,
        'blinded',
        CarbonImmutable::parse($summary['generated_at']),
        $manager,
    );

    /** @var ConditionTimerSummaryShareService $shareService */
    $shareService = app(ConditionTimerSummaryShareService::class);
    $share = $shareService->createShareForGroup($group, $manager);
    $shareUrl = route('shares.condition-timers.player-summary.show', $share->token);

    $response = $this->actingAs($manager)->get(route('campaigns.sessions.exports.markdown', [
        'campaign' => $campaign,
        'session' => $session,
    ]));

    $response->assertOk();

    $markdown = $response->getContent();

    expect($markdown)
        ->toContain('## Active Condition Outlook')
        ->toContain('### Specter Vanguard — Vault Approach')
        ->toContain('Blinded — 3 rounds remaining')
        ->toContain('Acknowledged by you.')
        ->toContain('Acknowledged by 1 party member.')
        ->toContain($shareUrl)
        ->toContain('Timer started at 3 rounds.')
        ->toContain('## Condition Timer Chronicle')
        ->toContain('Specter Vanguard • Blinded');
});

it('renders reward ledger entries in markdown exports for all viewers', function () {
    [$manager, $campaign, $group, $session] = seedCampaignSession();

    $player = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    SessionReward::create([
        'campaign_id' => $campaign->id,
        'campaign_session_id' => $session->id,
        'recorded_by' => $manager->id,
        'reward_type' => SessionReward::TYPE_LOOT,
        'title' => 'Moonstone circlet',
        'quantity' => 1,
        'awarded_to' => 'Lyra',
        'notes' => 'Glows in moonlight.',
    ]);

    SessionReward::create([
        'campaign_id' => $campaign->id,
        'campaign_session_id' => $session->id,
        'recorded_by' => $player->id,
        'reward_type' => SessionReward::TYPE_XP,
        'title' => 'Combat training',
        'quantity' => 250,
    ]);

    $managerExport = $this->actingAs($manager)->get(route('campaigns.sessions.exports.markdown', [
        'campaign' => $campaign,
        'session' => $session,
    ]));

    $managerExport->assertOk();
    expect($managerExport->getContent())
        ->toContain('Moonstone circlet')
        ->toContain('Lyra')
        ->toContain('Combat training');

    $playerExport = $this->actingAs($player)->get(route('campaigns.sessions.exports.markdown', [
        'campaign' => $campaign,
        'session' => $session,
    ]));

    $playerExport->assertOk();
    expect($playerExport->getContent())
        ->toContain('Moonstone circlet')
        ->toContain('Combat training');
});
