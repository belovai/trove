<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Module routes are registered by their own service providers, which is why
// they declare the `web` group themselves; this file is already inside it.
// Only the landing page, which belongs to no module, lives here.
Route::get('/', HomeController::class)->name('home');
