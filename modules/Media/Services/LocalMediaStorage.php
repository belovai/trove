<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Contracts\MediaStorage;
use Modules\Media\Enums\ThumbnailSize;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LocalMediaStorage implements MediaStorage
{
    public function __construct(
        private readonly string $disk,
    ) {}

    public function storeOriginal(UploadedFile $file, string $hashId): string
    {
        // The extension is derived from the detected MIME type, never from the
        // client-supplied filename: that string is attacker-controlled.
        $extension = $this->extensionFor($file->getMimeType());
        $path = "media/originals/{$hashId}/original.{$extension}";

        $this->disk()->put($path, $file->get());

        return $path;
    }

    public function storeThumbnail(string $hashId, ThumbnailSize $size, string $contents): string
    {
        $path = "media/thumbnails/{$hashId}/{$size->value}.webp";

        $this->disk()->put($path, $contents);

        return $path;
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function stream(string $path, string $mimeType, string $filename, array $headers = []): StreamedResponse
    {
        return $this->disk()->response($path, $filename, array_merge([
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ], $headers), 'inline');
    }

    public function delete(string $hashId): void
    {
        $this->disk()->deleteDirectory("media/originals/{$hashId}");
        $this->disk()->deleteDirectory("media/thumbnails/{$hashId}");
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    public function path(string $path): string
    {
        return $this->disk()->path($path);
    }

    private function disk(): Filesystem
    {
        return Storage::disk($this->disk);
    }

    private function extensionFor(?string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
            default => 'bin',
        };
    }
}
