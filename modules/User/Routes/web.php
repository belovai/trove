<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\ChangeAccountPasswordController;
use Modules\User\Controllers\DeleteAccountController;
use Modules\User\Controllers\ShowAccountController;
use Modules\User\Controllers\UpdateAccountController;

Route::middleware(['web', 'auth'])->prefix('account')->name('account.')->group(function (): void {
    Route::get('/', ShowAccountController::class)->name('show');
    Route::patch('/', UpdateAccountController::class)->name('update');
    Route::patch('password', ChangeAccountPasswordController::class)->name('password');
    Route::delete('/', DeleteAccountController::class)->name('destroy');
});
