<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\ChangeAccountPasswordController;
use Modules\User\Controllers\DeleteAccountController;
use Modules\User\Controllers\GenerateUserPasswordController;
use Modules\User\Controllers\IndexUsersController;
use Modules\User\Controllers\ServeAvatarController;
use Modules\User\Controllers\ShowAccountSettingsController;
use Modules\User\Controllers\ShowProfileController;
use Modules\User\Controllers\ShowProfileSettingsController;
use Modules\User\Controllers\StoreUserController;
use Modules\User\Controllers\UpdateAccountController;
use Modules\User\Controllers\UpdateAvatarController;
use Modules\User\Controllers\UpdateUserController;

Route::middleware('web')->group(function (): void {
    Route::get('avatars/{user:username}', ServeAvatarController::class)->name('avatar.show');
    Route::get('u/{user:username}', ShowProfileController::class)->name('profile.show');
});

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::redirect('settings', '/settings/account');
    // The account page moved under /settings; the old URL is still linked
    // from bookmarks and from older flash redirects.
    Route::redirect('account', '/settings/account');

    Route::prefix('settings')->name('settings.')->group(function (): void {
        Route::get('account', ShowAccountSettingsController::class)->name('account');
        Route::get('profile', ShowProfileSettingsController::class)->name('profile');
        Route::get('users', IndexUsersController::class)->name('users');
        Route::post('users', StoreUserController::class)->name('users.store');
        Route::patch('users/{user}', UpdateUserController::class)->name('users.update');
        Route::post('users/{user}/password', GenerateUserPasswordController::class)->name('users.password');
    });

    // Write endpoints keep their paths: only the pages moved.
    Route::patch('account', UpdateAccountController::class)->name('account.update');
    Route::patch('account/avatar', UpdateAvatarController::class)->name('account.avatar.update');
    Route::patch('account/password', ChangeAccountPasswordController::class)->name('account.password');
    Route::delete('account', DeleteAccountController::class)->name('account.destroy');
});
