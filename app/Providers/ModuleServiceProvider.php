<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\HasLevel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;

abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * The module key, derived from the provider class name.
     * UserModuleServiceProvider -> "user".
     */
    public function key(): string
    {
        $name = Str::before(class_basename(static::class), 'ModuleServiceProvider');

        return Str::snake($name);
    }

    /**
     * Subclasses that override boot() must call parent::boot().
     */
    public function boot(): void
    {
        $this->registerMigrations();
        $this->registerTranslations();
        $this->registerRoutes();
        $this->registerPrivileges();
    }

    /**
     * Absolute path to the module folder, i.e. the parent of Providers/.
     */
    protected function moduleBasePath(): string
    {
        return dirname((string) (new ReflectionClass(static::class))->getFileName(), 2);
    }

    private function registerMigrations(): void
    {
        $path = $this->moduleBasePath().'/Database/Migrations';

        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    private function registerTranslations(): void
    {
        $path = $this->moduleBasePath().'/Lang';

        if (is_dir($path)) {
            $this->loadTranslationsFrom($path, $this->key());
        }
    }

    private function registerRoutes(): void
    {
        $path = $this->moduleBasePath().'/Routes/web.php';

        if (is_file($path)) {
            $this->loadRoutesFrom($path);
        }
    }

    /**
     * Turn modules/{Module}/Config/privileges.php into one gate per entry,
     * prefixed with the module key: "media.upload", "tag.merge".
     *
     * The closure is deliberately untyped: this app-level base class must not
     * depend on the User module's model.
     */
    private function registerPrivileges(): void
    {
        $path = $this->moduleBasePath().'/Config/privileges.php';

        if (!is_file($path)) {
            return;
        }

        /** @var array<string, HasLevel> $privileges */
        $privileges = require $path;

        foreach ($privileges as $ability => $minimumRank) {
            Gate::define(
                "{$this->key()}.{$ability}",
                fn ($user) => $user->rank->level() >= $minimumRank->level(),
            );
        }
    }
}
