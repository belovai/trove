<?php

declare(strict_types=1);

namespace Modules\Mail\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Queue;
use Modules\Mail\Support\MailConfigurator;

final class MailModuleServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MailConfigurator::class);
    }

    public function boot(): void
    {
        parent::boot();

        // booted(): the settings table is only readable once the database
        // connection and the setting registry are available.
        $this->app->booted(function (): void {
            $this->app->make(MailConfigurator::class)->apply();
        });

        // A worker process outlives any single settings value, so every job
        // starts from what is stored now.
        Queue::before(function (): void {
            $this->app->make(MailConfigurator::class)->apply();
        });
    }
}
