<?php

use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\SessionAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createSessionWithMembers(): array
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
    ]);

    $player = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    return [$campaign, $session, $manager, $player];
}

it('allows campaign members to RSVP to a session', function () {
    [$campaign, $session, $manager, $player] = createSessionWithMembers();

    $response = $this->actingAs($player)->post(route('campaigns.sessions.attendance.store', [$campaign, $session]), [
        'status' => SessionAttendance::STATUS_YES,
        'note' => 'I will dial in remotely from the capital.',
    ]);

    $response->assertRedirect();

    $attendance = SessionAttendance::query()
        ->where('campaign_session_id', $session->id)
        ->where('user_id', $player->id)
        ->first();

    expect($attendance)->not->toBeNull();
    expect($attendance->status)->toBe(SessionAttendance::STATUS_YES);
    expect($attendance->note)->toContain('dial in');
    expect($attendance->responded_at)->not->toBeNull();
});

it('prevents outsiders from responding to session attendance', function () {
    [$campaign, $session, $manager] = createSessionWithMembers();
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)->post(route('campaigns.sessions.attendance.store', [$campaign, $session]), [
        'status' => SessionAttendance::STATUS_MAYBE,
    ]);

    $response->assertForbidden();

    expect(SessionAttendance::query()->exists())->toBeFalse();
});

it('allows members to clear their RSVP', function () {
    [$campaign, $session, $manager, $player] = createSessionWithMembers();

    SessionAttendance::factory()->create([
        'campaign_session_id' => $session->id,
        'user_id' => $player->id,
        'status' => SessionAttendance::STATUS_NO,
    ]);

    $response = $this->actingAs($player)->delete(route('campaigns.sessions.attendance.destroy', [$campaign, $session]));

    $response->assertRedirect();

    expect(
        SessionAttendance::query()
            ->where('campaign_session_id', $session->id)
            ->where('user_id', $player->id)
            ->exists()
    )->toBeFalse();
});
