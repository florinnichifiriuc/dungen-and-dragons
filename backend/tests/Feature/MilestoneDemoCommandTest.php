<?php

use Illuminate\Support\Facades\Artisan;

it('renders the latest milestone by default', function () {
    $exitCode = Artisan::call('demo:milestones', [
        '--source' => 'tests/Fixtures/progress_log_fixture.md',
        '--delay' => 0,
    ]);

    expect($exitCode)->toBe(0);

    $output = Artisan::output();

    expect($output)
        ->toContain('Milestone')
        ->toContain('Second Feature Launch')
        ->not->toContain('First Feature Launch')
        ->not->toContain('No milestone history');
});

it('filters milestones by a provided search term', function () {
    $exitCode = Artisan::call('demo:milestones', [
        'milestone' => 'First Feature',
        '--source' => 'tests/Fixtures/progress_log_fixture.md',
        '--delay' => 0,
        '--all' => true,
    ]);

    expect($exitCode)->toBe(0);

    $output = Artisan::output();

    expect($output)
        ->toContain('First Feature Launch')
        ->not->toContain('Second Feature Launch');
});

it('gracefully reports missing matches', function () {
    $exitCode = Artisan::call('demo:milestones', [
        'milestone' => 'Nonexistent',
        '--source' => 'tests/Fixtures/progress_log_fixture.md',
        '--delay' => 0,
    ]);

    expect($exitCode)->toBe(1);

    $output = Artisan::output();

    expect($output)
        ->toContain('No milestones matched')
        ->toContain('First Feature Launch');
});
