<?php

declare(strict_types=1);

namespace App\Contracts;

use Modules\Setting\Support\SettingDefinition;

/**
 * App-level so ModuleServiceProvider can load every module's settings without
 * depending on the Setting module's implementation, the same way the privilege
 * loader avoids depending on the User module.
 */
interface SettingRegistry
{
    /**
     * @param  array<string, SettingDefinition>  $definitions
     */
    public function register(string $module, array $definitions): void;

    public function has(string $key): bool;

    public function get(string $key): SettingDefinition;

    /**
     * @return array<string, SettingDefinition>
     */
    public function all(): array;

    /**
     * @return array<string, SettingDefinition>
     */
    public function namespace(string $prefix): array;
}
