<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignInvitationController;
use App\Http\Controllers\CampaignRoleAssignmentController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\RegionTurnController;
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
    Route::resource('groups', GroupController::class);
    Route::resource('groups.regions', RegionController::class)->except(['index'])->scoped();
    Route::get('groups/{group}/regions/{region}/turns/create', [RegionTurnController::class, 'create'])->name('groups.regions.turns.create');
    Route::post('groups/{group}/regions/{region}/turns', [RegionTurnController::class, 'store'])->name('groups.regions.turns.store');
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/invitations', [CampaignInvitationController::class, 'store'])->name('campaigns.invitations.store');
    Route::delete('campaigns/{campaign}/invitations/{invitation}', [CampaignInvitationController::class, 'destroy'])->name('campaigns.invitations.destroy');
    Route::post('campaigns/{campaign}/assignments', [CampaignRoleAssignmentController::class, 'store'])->name('campaigns.assignments.store');
    Route::delete('campaigns/{campaign}/assignments/{assignment}', [CampaignRoleAssignmentController::class, 'destroy'])->name('campaigns.assignments.destroy');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
