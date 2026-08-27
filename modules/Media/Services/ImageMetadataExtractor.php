<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use Imagick;
use Intervention\Image\ImageManager;
use Modules\Media\Contracts\MediaStorage;
use Modules\Media\Contracts\MetadataExtractor;
use Modules\Media\DataObjects\ExtractedMetadata;

final class ImageMetadataExtractor implements MetadataExtractor
{
    public function __construct(
        private readonly MediaStorage $storage,
        private readonly ImageManager $images,
    ) {}

    public function extract(string $storagePath, string $mimeType): ExtractedMetadata
    {
        $absolute = $this->storage->path($storagePath);
        $image = $this->images->read($absolute);

        $frameCount = $this->frameCount($absolute, $mimeType);

        return new ExtractedMetadata(
            width: $image->width(),
            height: $image->height(),
            isAnimated: $frameCount !== null && $frameCount > 1,
            frameCount: $frameCount !== null && $frameCount > 1 ? $frameCount : null,
            // The average color of the whole image, obtained by resizing it to
            // a single pixel. Cheap, and good enough for a loading placeholder.
            // toHex() includes an alpha channel for transparent images
            // (#rrggbbaa), which overflows the varchar(7) column — keep RGB only.
            dominantColor: substr($this->images->read($absolute)->resize(1, 1)->pickColor(0, 0)->toHex('#'), 0, 7),
        );
    }

    /**
     * Only container formats can animate. Returns null when the format cannot,
     * or when the imagick extension is unavailable.
     */
    private function frameCount(string $absolute, string $mimeType): ?int
    {
        if (!in_array($mimeType, ['image/gif', 'image/webp', 'image/avif'], true)) {
            return null;
        }

        if (!extension_loaded('imagick')) {
            return null;
        }

        $imagick = new Imagick($absolute);

        return $imagick->getNumberImages();
    }
}
