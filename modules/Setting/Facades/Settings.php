<?php

declare(strict_types=1);

namespace Modules\Setting\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Setting\SettingManager;

/**
 * @method static mixed get(string $key)
 * @method static void set(string $key, mixed $value)
 * @method static void forget(string $key)
 * @method static array<string, mixed> namespace(string $prefix)
 * @method static void flush()
 *
 * @see SettingManager
 */
final class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SettingManager::class;
    }
}
