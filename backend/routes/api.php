<?php

use App\Http\Controllers\Auth\AuthenticatedUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', AuthenticatedUserController::class)->name('api.auth.me');

    // Future API routes
});
