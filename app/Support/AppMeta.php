<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Build identity shown on the About block: version, commit, build time.
 *
 * Sourced from `meta.json` at the repo root — gitignored, written by
 * `php artisan meta:generate` locally or by the Docker release workflow
 * before the image is built. Falls back to composer.json's version with no
 * commit/build time when the file has never been generated (plain local dev).
 */
final class AppMeta
{
    /**
     * @return array{version: string, commit: ?string, built_at: ?string}
     */
    public static function current(): array
    {
        $path = base_path('meta.json');

        if (File::isFile($path)) {
            /** @var array{version?: string, commit?: string, built_at?: string} $meta */
            $meta = File::json($path);

            return [
                'version' => $meta['version'] ?? self::composerVersion(),
                'commit' => $meta['commit'] ?? null,
                'built_at' => $meta['built_at'] ?? null,
            ];
        }

        return [
            'version' => self::composerVersion(),
            'commit' => null,
            'built_at' => null,
        ];
    }

    private static function composerVersion(): string
    {
        /** @var array{version?: string} $composer */
        $composer = File::json(base_path('composer.json'));

        return $composer['version'] ?? 'dev';
    }
}
