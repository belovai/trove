<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Modules\User\Contracts\AvatarStorage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LocalAvatarStorage implements AvatarStorage
{
    public function __construct(
        private readonly string $disk,
    ) {}

    public function store(int $userId, string $contents): string
    {
        $path = "avatars/{$userId}.webp";

        $this->disk()->put($path, $contents);

        return $path;
    }

    public function delete(string $path): void
    {
        $this->disk()->delete($path);
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    public function stream(string $path, string $filename): StreamedResponse
    {
        return $this->disk()->response($path, $filename, [
            'Content-Type' => 'image/webp',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ], 'inline');
    }

    private function disk(): Filesystem
    {
        return Storage::disk($this->disk);
    }
}
