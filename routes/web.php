<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware([
    'auth',
    'can:dashboard.view',
])->group(function () {
    Route::view(
        '/dashboard',
        'dashboard'
    )->name('dashboard');
});

Route::middleware([
    'auth',
    'can:projects.view',
])->group(function () {
    Route::view(
        '/projects',
        'projects.index'
    )->name('projects.index');
});

Route::middleware([
    'auth',
    'can:master-data.view',
])
    ->prefix('administration')
    ->group(function () {

        Route::view(
            '/master-data',
            'administration.master-data.index'
        )->name('master-data.index');

        Route::view(
            '/locations',
            'administration.locations.index'
        )->name('locations.index');
    });