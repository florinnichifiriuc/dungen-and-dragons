<?php

use App\Http\Controllers\Admin\BugReportController;
use App\Models\BugReport;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;

uses(RefreshDatabase::class);

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('filters bug reports by updated timeframe', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-11-24 12:00', 'UTC'));

    $admin = User::factory()->supportAdmin()->create();

    $recent = BugReport::factory()->create([
        'summary' => 'Recent issue',
        'created_at' => CarbonImmutable::now()->subHours(6),
        'updated_at' => CarbonImmutable::now()->subHours(6),
    ]);

    $older = BugReport::factory()->create([
        'summary' => 'Older issue',
        'created_at' => CarbonImmutable::now()->subDays(3),
        'updated_at' => CarbonImmutable::now()->subDays(3),
    ]);

    Config::set('inertia.testing.ensure_manifest', false);

    Gate::shouldReceive('authorize')
        ->once()
        ->with('viewAny', \App\Models\BugReport::class)
        ->andReturnTrue();

    $request = Request::create('/admin/bug-reports', 'GET', ['timeframe' => '24h']);
    $request->setUserResolver(fn () => $admin);
    $request->headers->set('X-Inertia', 'true');

    $controller = app(BugReportController::class);

    $response = $controller->index($request)->toResponse($request);
    $page = $response instanceof JsonResponse ? $response->getData(true) : [];

    expect($page['component'])->toBe('Admin/BugReports/Index');
    expect($page['props']['filters']['timeframe'])->toBe('24h');
    expect(collect($page['props']['reports']['data'])->pluck('id')->all())->toBe([$recent->id]);

    Gate::shouldReceive('authorize')
        ->once()
        ->with('viewAny', \App\Models\BugReport::class)
        ->andReturnTrue();

    $request = Request::create('/admin/bug-reports', 'GET', ['timeframe' => '7d']);
    $request->setUserResolver(fn () => $admin);
    $request->headers->set('X-Inertia', 'true');

    $response = $controller->index($request)->toResponse($request);
    $page = $response instanceof JsonResponse ? $response->getData(true) : [];

    expect($page['props']['filters']['timeframe'])->toBe('7d');
    expect(collect($page['props']['reports']['data'])->pluck('id')->sort()->values()->all())
        ->toEqualCanonicalizing([$recent->id, $older->id]);
});
