<?php

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\ConditionTimerEscalatedNotification;
use App\Services\ConditionTimerSummaryProjector;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('dispatches escalation notifications when urgency increases', function (): void {
    Notification::fake();

    $user = User::factory()->create(['timezone' => 'UTC']);
    $group = Group::factory()->create(['created_by' => $user->id]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_DUNGEON_MASTER,
    ]);

    $map = Map::factory()->for($group)->create(['region_id' => null]);

    $token = MapToken::factory()
        ->for($map)
        ->create([
            'status_conditions' => ['poisoned'],
            'status_condition_durations' => ['poisoned' => 6],
        ]);

    /** @var ConditionTimerSummaryProjector $projector */
    $projector = app(ConditionTimerSummaryProjector::class);

    $projector->refreshForGroup($group);

    Notification::assertNothingSent();

    $token->refresh();
    $token->status_condition_durations = ['poisoned' => 2];
    $token->save();

    $projector->refreshForGroup($group);

    Notification::assertSentToTimes($user, ConditionTimerEscalatedNotification::class, 1);

    Notification::assertSentTo(
        $user,
        ConditionTimerEscalatedNotification::class,
        function (ConditionTimerEscalatedNotification $notification) use ($user) {
            $data = $notification->toArray($user);

            expect($data['urgency'])->toBe('critical');
            expect($data['condition']['key'])->toBe('poisoned');

            return true;
        }
    );
});

it('suppresses disruptive channels during quiet hours when no in-app alerts are enabled', function (): void {
    Notification::fake();

    $user = User::factory()->create(['timezone' => 'UTC']);
    $group = Group::factory()->create(['created_by' => $user->id]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    $map = Map::factory()->for($group)->create(['region_id' => null]);

    $token = MapToken::factory()
        ->for($map)
        ->create([
            'status_conditions' => ['frightened'],
            'status_condition_durations' => ['frightened' => 2],
        ]);

    NotificationPreference::forUser($user)->forceFill([
        'channel_in_app' => false,
        'channel_push' => true,
        'channel_email' => true,
        'quiet_hours_start' => '20:00',
        'quiet_hours_end' => '08:00',
        'digest_delivery' => 'off',
    ])->save();

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-11-05 21:30:00', 'UTC'));

    /** @var ConditionTimerSummaryProjector $projector */
    $projector = app(ConditionTimerSummaryProjector::class);

    $projector->refreshForGroup($group);

    Notification::assertNothingSent();

    CarbonImmutable::setTestNow();
});
