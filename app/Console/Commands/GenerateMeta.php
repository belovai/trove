<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Writes `meta.json` at the repo root for local development, mirroring what
 * the Docker release workflow writes before building the image (see
 * `.github/workflows/docker-release.yml`) — that path uses the release tag
 * and CI's own git checkout instead of shelling out.
 */
final class GenerateMeta extends Command
{
    protected $signature = 'meta:generate';

    protected $description = 'Write meta.json (version, commit, build time) at the repo root for the About block.';

    public function handle(): int
    {
        $version = trim(Process::path(base_path())->run('git describe --tags')->output())
            ?: (File::json(base_path('composer.json'))['version'] ?? 'dev');

        $commit = trim(Process::path(base_path())->run('git rev-parse --short HEAD')->output()) ?: null;

        file_put_contents(base_path('meta.json'), json_encode([
            'version' => $version,
            'commit' => $commit,
            'built_at' => now()->utc()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $this->info("Wrote meta.json ({$version}".($commit !== null ? "@{$commit}" : '').')');

        return self::SUCCESS;
    }
}
