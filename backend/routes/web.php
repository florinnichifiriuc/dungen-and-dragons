<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('welcome');

Route::get('/login', function () {
    return Inertia::render('Welcome', [
        'authIntent' => 'login',
    ]);
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Welcome', [
        'authIntent' => 'register',
    ]);
})->name('register');
