<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // No uncompromised(): it calls the Have I Been Pwned API, which is a poor
        // default for a self-hosted instance that may have no network at all.
        Password::defaults(fn () => Password::min(8)->letters()->numbers());
    }
}
