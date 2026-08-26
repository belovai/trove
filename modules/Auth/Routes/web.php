<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\LoginController;
use Modules\Auth\Controllers\LogoutController;
use Modules\Auth\Controllers\RegisterController;
use Modules\Auth\Controllers\ShowLoginController;
use Modules\Auth\Controllers\ShowRegisterController;

Route::middleware(['web', 'guest'])->group(function (): void {
    Route::get('login', ShowLoginController::class)->name('login');
    Route::post('login', LoginController::class)->middleware('throttle:6,1');

    if (config('trove.registration.mode') === 'open') {
        Route::get('register', ShowRegisterController::class)->name('register');
        Route::post('register', RegisterController::class)->middleware('throttle:6,1');
    }
});

Route::middleware(['web', 'auth'])
    ->post('logout', LogoutController::class)
    ->name('logout');
