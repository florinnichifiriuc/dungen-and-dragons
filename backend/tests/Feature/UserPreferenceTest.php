<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

it('renders the preference editor for authenticated users', function (): void {
    $user = User::factory()->create();
    config()->set('app.asset_url', 'http://localhost/build');
    $version = hash('xxh128', config('app.asset_url'));

    $response = actingAs($user)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ])
        ->get(route('settings.preferences.edit'));

    $response->assertOk();
    expect($response->json('component'))->toBe('Settings/Preferences');
});

it('persists updated accessibility preferences', function (): void {
    $user = User::factory()->create([
        'locale' => 'en',
        'timezone' => 'UTC',
        'theme' => 'system',
        'high_contrast' => false,
        'font_scale' => 100,
    ]);

    actingAs($user)
        ->from(route('settings.preferences.edit'))
        ->put(route('settings.preferences.update'), [
            'locale' => 'ro',
            'timezone' => 'Europe/Bucharest',
            'theme' => 'dark',
            'high_contrast' => true,
            'font_scale' => 125,
        ])
        ->assertRedirect(route('settings.preferences.edit'))
        ->assertSessionHas('success', Lang::get('app.preferences.success'));

    expect($user->fresh())->toMatchArray([
        'locale' => 'ro',
        'timezone' => 'Europe/Bucharest',
        'theme' => 'dark',
        'high_contrast' => true,
        'font_scale' => 125,
    ]);
});

it('validates unsupported preference values', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->put(route('settings.preferences.update'), [
            'locale' => 'xx',
            'timezone' => 'Mars/Olympus',
            'theme' => 'neon',
            'high_contrast' => 'maybe',
            'font_scale' => 200,
        ])
        ->assertSessionHasErrors(['locale', 'timezone', 'theme', 'high_contrast', 'font_scale']);
});
