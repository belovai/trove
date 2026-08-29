<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\LoginController;
use Modules\Auth\Controllers\LogoutController;
use Modules\Auth\Controllers\RegisterController;
use Modules\Auth\Controllers\ResendVerificationController;
use Modules\Auth\Controllers\ResetPasswordController;
use Modules\Auth\Controllers\SendPasswordResetLinkController;
use Modules\Auth\Controllers\ShowForgotPasswordController;
use Modules\Auth\Controllers\ShowLoginController;
use Modules\Auth\Controllers\ShowRegisterController;
use Modules\Auth\Controllers\ShowResetPasswordController;
use Modules\Auth\Controllers\ShowVerifyEmailController;
use Modules\Auth\Controllers\VerifyEmailController;
use Modules\Auth\Middleware\EnsureRegistrationIsOpen;

Route::middleware(['web', 'guest'])->group(function (): void {
    Route::get('login', ShowLoginController::class)->name('login');
    Route::post('login', LoginController::class)->middleware('throttle:6,1');

    Route::get('forgot-password', ShowForgotPasswordController::class)->name('password.request');
    Route::post('forgot-password', SendPasswordResetLinkController::class)
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('reset-password/{token}', ShowResetPasswordController::class)->name('password.reset');
    Route::post('reset-password', ResetPasswordController::class)
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Always registered — see EnsureRegistrationIsOpen for why.
    Route::middleware(EnsureRegistrationIsOpen::class)->group(function (): void {
        Route::get('register', ShowRegisterController::class)->name('register');
        Route::post('register', RegisterController::class)->middleware('throttle:6,1');
    });
});

Route::middleware(['web', 'auth'])
    ->post('logout', LogoutController::class)
    ->name('logout');

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('verify-email', ShowVerifyEmailController::class)->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', ResendVerificationController::class)
        ->middleware('throttle:6,1')
        ->name('verification.send');
});
