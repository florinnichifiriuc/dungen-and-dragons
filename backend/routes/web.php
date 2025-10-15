<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignInvitationController;
use App\Http\Controllers\CampaignRoleAssignmentController;
use App\Http\Controllers\DiceRollController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupJoinController;
use App\Http\Controllers\GroupMembershipController;
use App\Http\Controllers\InitiativeEntryController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\RegionTurnController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MapTileController;
use App\Http\Controllers\TileTemplateController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SessionNoteController;
use App\Http\Controllers\WorldController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('welcome');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('groups/join', [GroupJoinController::class, 'create'])->name('groups.join');
    Route::post('groups/join', [GroupJoinController::class, 'store'])->name('groups.join.store');
    Route::resource('groups', GroupController::class);
    Route::resource('groups.memberships', GroupMembershipController::class)
        ->only(['store', 'update', 'destroy'])
        ->scoped();
    Route::resource('groups.worlds', WorldController::class)
        ->except(['index', 'show'])
        ->scoped();
    Route::resource('groups.regions', RegionController::class)->except(['index'])->scoped();
    Route::resource('groups.tile-templates', TileTemplateController::class)
        ->except(['index', 'show'])
        ->scoped();
    Route::resource('groups.maps', MapController::class)
        ->except(['index'])
        ->scoped();
    Route::resource('groups.maps.tiles', MapTileController::class)
        ->only(['store', 'update', 'destroy'])
        ->scoped();
    Route::get('groups/{group}/regions/{region}/turns/create', [RegionTurnController::class, 'create'])->name('groups.regions.turns.create');
    Route::post('groups/{group}/regions/{region}/turns', [RegionTurnController::class, 'store'])->name('groups.regions.turns.store');
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/invitations', [CampaignInvitationController::class, 'store'])->name('campaigns.invitations.store');
    Route::delete('campaigns/{campaign}/invitations/{invitation}', [CampaignInvitationController::class, 'destroy'])->name('campaigns.invitations.destroy');
    Route::post('campaigns/{campaign}/assignments', [CampaignRoleAssignmentController::class, 'store'])->name('campaigns.assignments.store');
    Route::delete('campaigns/{campaign}/assignments/{assignment}', [CampaignRoleAssignmentController::class, 'destroy'])->name('campaigns.assignments.destroy');
    Route::resource('campaigns.sessions', SessionController::class);
    Route::post('campaigns/{campaign}/sessions/{session}/notes', [SessionNoteController::class, 'store'])->name('campaigns.sessions.notes.store');
    Route::patch('campaigns/{campaign}/sessions/{session}/notes/{note}', [SessionNoteController::class, 'update'])->name('campaigns.sessions.notes.update');
    Route::delete('campaigns/{campaign}/sessions/{session}/notes/{note}', [SessionNoteController::class, 'destroy'])->name('campaigns.sessions.notes.destroy');
    Route::post('campaigns/{campaign}/sessions/{session}/dice-rolls', [DiceRollController::class, 'store'])->name('campaigns.sessions.dice-rolls.store');
    Route::delete('campaigns/{campaign}/sessions/{session}/dice-rolls/{roll}', [DiceRollController::class, 'destroy'])->name('campaigns.sessions.dice-rolls.destroy');
    Route::post('campaigns/{campaign}/sessions/{session}/initiative', [InitiativeEntryController::class, 'store'])->name('campaigns.sessions.initiative.store');
    Route::patch('campaigns/{campaign}/sessions/{session}/initiative/{entry}', [InitiativeEntryController::class, 'update'])->name('campaigns.sessions.initiative.update');
    Route::delete('campaigns/{campaign}/sessions/{session}/initiative/{entry}', [InitiativeEntryController::class, 'destroy'])->name('campaigns.sessions.initiative.destroy');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
