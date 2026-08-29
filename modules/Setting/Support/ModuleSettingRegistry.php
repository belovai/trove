<?php

declare(strict_types=1);

namespace Modules\Setting\Support;

use App\Contracts\SettingRegistry;
use Illuminate\Support\Str;
use Modules\Setting\Exceptions\DuplicateSettingException;
use Modules\Setting\Exceptions\UnknownSettingException;

final class ModuleSettingRegistry implements SettingRegistry
{
    /** @var array<string, SettingDefinition> */
    private array $definitions = [];

    /** @var array<string, string> key => declaring module */
    private array $owners = [];

    /**
     * @param  array<string, SettingDefinition>  $definitions
     */
    public function register(string $module, array $definitions): void
    {
        foreach ($definitions as $key => $definition) {
            // Last-one-wins on a duplicated key would be a very hard bug to
            // find later, so it is a boot-time failure instead.
            if (isset($this->definitions[$key])) {
                throw DuplicateSettingException::for($key, $this->owners[$key], $module);
            }

            $this->definitions[$key] = $definition;
            $this->owners[$key] = $module;
        }
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    public function get(string $key): SettingDefinition
    {
        return $this->definitions[$key] ?? throw UnknownSettingException::for($key);
    }

    /**
     * @return array<string, SettingDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * @return array<string, SettingDefinition>
     */
    public function namespace(string $prefix): array
    {
        return array_filter(
            $this->definitions,
            fn (string $key): bool => Str::before($key, '.') === $prefix,
            ARRAY_FILTER_USE_KEY,
        );
    }
}
