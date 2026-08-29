<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Mail\Controllers\SendTestMailController;
use Modules\Mail\Controllers\ShowMailSettingsController;
use Modules\Mail\Controllers\UpdateMailSettingsController;

Route::middleware(['web', 'auth'])->prefix('settings')->group(function (): void {
    Route::get('mail', ShowMailSettingsController::class)
        ->middleware('can:setting.manage')
        ->name('settings.mail');

    // Authorization for the write lives in the FormRequest, so a rejected key
    // and a rejected user come back through the same path.
    Route::patch('mail', UpdateMailSettingsController::class);

    // An outbound network call behind a form button, so it is throttled.
    Route::post('mail/test', SendTestMailController::class)
        ->middleware('throttle:5,1')
        ->name('settings.mail.test');
});
