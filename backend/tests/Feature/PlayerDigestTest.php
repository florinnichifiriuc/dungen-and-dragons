<?php

use App\Jobs\SendPlayerDigest;
use App\Models\Campaign;
use App\Models\CampaignQuest;
use App\Models\CampaignQuestUpdate;
use App\Models\CampaignRoleAssignment;
use App\Models\CampaignSession;
use App\Models\ConditionTimerAdjustment;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\NotificationPreference;
use App\Models\SessionReward;
use App\Models\User;
use App\Services\PlayerDigestService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('builds digest payload with updates across systems', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-11-06 10:00:00', 'UTC'));

    $user = User::factory()->create(['name' => 'Lyra Starwhisper']);
    $group = Group::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $map = Map::factory()->create(['group_id' => $group->id]);
    $token = MapToken::factory()->create([
        'map_id' => $map->id,
        'name' => 'Acolyte Scout',
        'hidden' => false,
        'faction' => MapToken::FACTION_ALLIED,
    ]);

    ConditionTimerAdjustment::create([
        'group_id' => $group->id,
        'map_token_id' => $token->id,
        'condition_key' => 'poisoned',
        'previous_rounds' => 3,
        'new_rounds' => 1,
        'delta' => -2,
        'reason' => 'manual_adjustment',
        'context' => ['source' => 'test'],
        'actor_id' => null,
        'actor_role' => null,
        'recorded_at' => CarbonImmutable::now('UTC')->subHours(2),
    ]);

    $campaign = Campaign::factory()->create(['group_id' => $group->id]);

    CampaignRoleAssignment::create([
        'campaign_id' => $campaign->id,
        'assignee_type' => User::class,
        'assignee_id' => $user->id,
        'role' => CampaignRoleAssignment::ROLE_PLAYER,
        'scope' => 'campaign',
        'status' => 'active',
        'assigned_by' => $campaign->created_by,
        'accepted_at' => Carbon::now('UTC'),
    ]);

    $quest = CampaignQuest::factory()->create([
        'campaign_id' => $campaign->id,
        'region_id' => $campaign->region_id,
    ]);

    CampaignQuestUpdate::create([
        'quest_id' => $quest->id,
        'created_by_id' => $campaign->created_by,
        'summary' => 'Unearthed a new lead in the ruins.',
        'details' => 'The party found a sealed vault with sigils matching the prophecy.',
        'recorded_at' => CarbonImmutable::now('UTC')->subHour(),
    ]);

    $session = CampaignSession::factory()->create([
        'campaign_id' => $campaign->id,
        'created_by' => $campaign->created_by,
    ]);

    SessionReward::create([
        'id' => (string) Str::uuid(),
        'campaign_id' => $campaign->id,
        'campaign_session_id' => $session->id,
        'recorded_by' => $campaign->created_by,
        'reward_type' => SessionReward::TYPE_LOOT,
        'title' => 'Moonlit Signet',
        'quantity' => 1,
        'awarded_to' => 'Lyra',
        'notes' => 'Glows when the veil thins.',
        'created_at' => Carbon::now('UTC')->subMinutes(90),
        'updated_at' => Carbon::now('UTC')->subMinutes(90),
    ]);

    $service = app(PlayerDigestService::class);
    $digest = $service->build($user, CarbonImmutable::now('UTC')->subHours(4), 'full');

    expect($digest['has_updates'])->toBeTrue();
    expect($digest['sections']['conditions'])->toHaveCount(1);
    expect($digest['sections']['quests'])->toHaveCount(1);
    expect($digest['sections']['rewards'])->toHaveCount(1);
    expect($digest['markdown'])->toContain('Condition highlights');
});

it('skips digest delivery during quiet hours without force', function (): void {
    Notification::fake();
    Carbon::setTestNow(Carbon::parse('2025-11-06 22:15:00', 'UTC'));

    $user = User::factory()->create();
    $group = Group::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $map = Map::factory()->create(['group_id' => $group->id]);
    $token = MapToken::factory()->create([
        'map_id' => $map->id,
        'name' => 'Watcher',
        'hidden' => false,
        'faction' => MapToken::FACTION_ALLIED,
    ]);

    ConditionTimerAdjustment::create([
        'group_id' => $group->id,
        'map_token_id' => $token->id,
        'condition_key' => 'burning',
        'previous_rounds' => 2,
        'new_rounds' => 2,
        'delta' => 0,
        'reason' => 'turn_tick',
        'context' => [],
        'actor_id' => null,
        'actor_role' => null,
        'recorded_at' => CarbonImmutable::now('UTC')->subMinutes(30),
    ]);

    $campaign = Campaign::factory()->create(['group_id' => $group->id]);

    CampaignRoleAssignment::create([
        'campaign_id' => $campaign->id,
        'assignee_type' => User::class,
        'assignee_id' => $user->id,
        'role' => CampaignRoleAssignment::ROLE_PLAYER,
        'scope' => 'campaign',
        'status' => 'active',
        'assigned_by' => $campaign->created_by,
        'accepted_at' => Carbon::now('UTC'),
    ]);

    NotificationPreference::forUser($user)->forceFill([
        'digest_delivery' => 'daily',
        'digest_channel_in_app' => true,
        'digest_channel_email' => false,
        'digest_channel_push' => false,
        'quiet_hours_start' => '21:00',
        'quiet_hours_end' => '07:00',
    ])->save();

    SendPlayerDigest::dispatchSync($user->id);

    Notification::assertNothingSent();
});
