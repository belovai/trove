<?php

declare(strict_types=1);

namespace Modules\Setting\Providers;

use App\Contracts\SettingRegistry;
use App\Providers\ModuleServiceProvider;
use Modules\Setting\SettingManager;
use Modules\Setting\Support\ModuleSettingRegistry;

final class SettingModuleServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingRegistry::class, ModuleSettingRegistry::class);
        $this->app->singleton(SettingManager::class);
    }
}
