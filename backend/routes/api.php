<?php

use App\Http\Controllers\Api\SessionNpcDialogueController;
use App\Http\Controllers\Auth\AuthenticatedUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', AuthenticatedUserController::class)->name('api.auth.me');

    Route::post(
        '/campaigns/{campaign}/sessions/{session}/npc-dialogue',
        SessionNpcDialogueController::class
    )->name('api.campaigns.sessions.npc-dialogue');
});
