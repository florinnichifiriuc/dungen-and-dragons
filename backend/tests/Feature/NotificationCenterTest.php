<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

uses(RefreshDatabase::class);

it('renders the notification center with existing records', function (): void {
    $user = User::factory()->create();

    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'condition',
        'notifiable_type' => $user::class,
        'notifiable_id' => $user->id,
        'data' => ['title' => 'Test notification', 'urgency' => 'critical'],
    ]);

    config()->set('app.asset_url', 'http://localhost/build');
    $version = hash('xxh128', config('app.asset_url'));

    $response = actingAs($user)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ])
        ->get(route('notifications.index'));

    $response->assertOk();
    expect($response->json('component'))->toBe('Notifications/Index');
});

it('marks a notification as read', function (): void {
    $user = User::factory()->create();

    $notification = DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'condition',
        'notifiable_type' => $user::class,
        'notifiable_id' => $user->id,
        'data' => ['title' => 'Needs action'],
    ]);

    actingAs($user)
        ->from(route('notifications.index'))
        ->patch(route('notifications.read', $notification), ['_token' => csrf_token()])
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not()->toBeNull();
});
