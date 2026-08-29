<?php

declare(strict_types=1);

namespace Modules\Setting\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Setting\Models\Setting;
use Throwable;

/**
 * The only class that touches the settings table.
 */
final class SettingRepository
{
    /**
     * The whole table is one cache entry rather than one per key: it is a few
     * dozen rows, and one entry means one invalidation point. Cache tags are
     * unavailable — the default store is `file`.
     */
    public const CACHE_KEY = 'trove.settings';

    /**
     * @return array<string, array{value: ?string, is_encrypted: bool}>
     */
    public function all(): array
    {
        try {
            // The `catch` sits outside `rememberForever`: `Cache::rememberForever`
            // only stores the callback's result if it returns rather than
            // throws, so a failure here — table not migrated yet, or a
            // transient DB error — is never the thing that gets cached. Only
            // a genuinely successful read is.
            /** @var array<string, array{value: ?string, is_encrypted: bool}> */
            return Cache::rememberForever(self::CACHE_KEY, function (): array {
                return Setting::query()
                    ->get(['key', 'value', 'is_encrypted'])
                    ->mapWithKeys(fn (Setting $setting): array => [
                        $setting->key => [
                            'value' => $setting->value,
                            'is_encrypted' => $setting->is_encrypted,
                        ],
                    ])
                    ->all();
            });
        } catch (Throwable $exception) {
            Log::warning('Could not read the settings table.', ['exception' => $exception]);

            return [];
        }
    }

    public function set(string $key, ?string $value, bool $isEncrypted): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'is_encrypted' => $isEncrypted],
        );

        Cache::forget(self::CACHE_KEY);
    }

    public function forget(string $key): void
    {
        Setting::query()->where('key', $key)->delete();

        Cache::forget(self::CACHE_KEY);
    }
}
