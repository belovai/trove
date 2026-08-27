<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

use Illuminate\Http\UploadedFile;
use Modules\Media\Enums\ThumbnailSize;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The only place in the application that knows what a media file path looks
 * like. Nothing else builds one.
 */
interface MediaStorage
{
    public function storeOriginal(UploadedFile $file, string $hashId): string;

    public function storeThumbnail(string $hashId, ThumbnailSize $size, string $contents): string;

    /**
     * @param  array<string, string>  $headers
     */
    public function stream(string $path, string $mimeType, string $filename, array $headers = []): StreamedResponse;

    /**
     * Removes the original and every thumbnail belonging to the id.
     */
    public function delete(string $hashId): void;

    public function exists(string $path): bool;

    /**
     * An absolute filesystem path, for libraries that cannot read a stream.
     */
    public function path(string $path): string;
}
