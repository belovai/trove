<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Setting\Controllers\ShowSystemSettingsController;
use Modules\Setting\Controllers\UpdateSystemSettingsController;

Route::middleware(['web', 'auth'])->prefix('settings')->group(function (): void {
    Route::get('system', ShowSystemSettingsController::class)
        ->middleware('can:setting.manage')
        ->name('settings.system');

    // Authorization for the write lives in the FormRequest, so a rejected key
    // and a rejected user come back through the same path.
    Route::patch('system', UpdateSystemSettingsController::class);
});
