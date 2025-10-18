<?php

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\ConditionTimerEscalatedNotification;
use App\Services\AnalyticsRecorder;
use App\Services\ConditionTimerEscalationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
});

afterEach(function (): void {
    \Mockery::close();
});

it('skips notifications when urgency does not increase', function () {
    $analytics = \Mockery::spy(AnalyticsRecorder::class);
    $service = new ConditionTimerEscalationService($analytics);

    $group = Group::factory()->create();

    $previous = [
        'entries' => [
            [
                'token' => ['id' => 1, 'label' => 'Scout'],
                'conditions' => [
                    [
                        'key' => 'poisoned',
                        'urgency' => 'warning',
                    ],
                ],
            ],
        ],
    ];

    $current = $previous;

    $service->handle($group, $previous, $current);

    Notification::assertNothingSent();
    $analytics->shouldNotHaveReceived('record');
});

it('dispatches notifications and analytics when urgency escalates', function () {
    $analytics = \Mockery::spy(AnalyticsRecorder::class);
    $service = new ConditionTimerEscalationService($analytics);

    $group = Group::factory()->create();
    $user = User::factory()->create(['timezone' => 'UTC']);

    GroupMembership::factory()->owner()->create([
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);

    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'channel_in_app' => true,
        'channel_push' => false,
        'channel_email' => false,
        'digest_delivery' => 'off',
    ]);

    $previous = [
        'entries' => [
            [
                'token' => ['id' => 1, 'label' => 'Scout'],
                'conditions' => [
                    [
                        'key' => 'poisoned',
                        'urgency' => 'calm',
                    ],
                ],
            ],
        ],
    ];

    $current = [
        'entries' => [
            [
                'token' => ['id' => 1, 'label' => 'Scout'],
                'conditions' => [
                    [
                        'key' => 'poisoned',
                        'urgency' => 'critical',
                        'label' => 'Poisoned',
                    ],
                ],
                'map' => ['id' => 12, 'name' => 'Grove'],
            ],
        ],
    ];

    $service->handle($group, $previous, $current);

    Notification::assertSentTo($user, ConditionTimerEscalatedNotification::class, function ($notification) use ($user) {
        return $notification->toArray($user)['urgency'] === 'critical';
    });

    $analytics->shouldHaveReceived('record')->atLeast()->once();
});

it('skips push and email during quiet hours', function () {
    $analytics = \Mockery::spy(AnalyticsRecorder::class);
    $service = new ConditionTimerEscalationService($analytics);

    $group = Group::factory()->create();
    $user = User::factory()->create(['timezone' => 'UTC']);

    GroupMembership::factory()->owner()->create([
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);

    NotificationPreference::factory()->withQuietHours('00:00', '23:59')->create([
        'user_id' => $user->id,
        'channel_in_app' => true,
        'channel_push' => true,
        'channel_email' => true,
        'digest_delivery' => 'off',
    ]);

    $current = [
        'entries' => [
            [
                'token' => ['id' => 1, 'label' => 'Scout'],
                'conditions' => [
                    [
                        'key' => 'poisoned',
                        'urgency' => 'critical',
                        'label' => 'Poisoned',
                    ],
                ],
                'map' => ['id' => 12, 'name' => 'Grove'],
            ],
        ],
    ];

    $service->handle($group, null, $current);

    Notification::assertSentTo($user, ConditionTimerEscalatedNotification::class, function ($notification, $channels) {
        expect($channels)->toContain('database');
        expect($channels)->not->toContain('mail');

        return true;
    });
});
