<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use Intervention\Image\ImageManager;
use Modules\Media\Contracts\MediaStorage;
use Modules\Media\Contracts\ThumbnailGenerator;
use Modules\Media\Enums\ThumbnailSize;
use Modules\Media\Models\Media;

final class InterventionThumbnailGenerator implements ThumbnailGenerator
{
    public function __construct(
        private readonly MediaStorage $storage,
        private readonly ImageManager $images,
    ) {}

    public function generate(Media $media, ThumbnailSize $size): string
    {
        // read() takes the first frame of an animated source, which is exactly
        // what a static thumbnail needs.
        $image = $this->images->read($this->storage->path($media->storage_path));

        $image = $size->isSquareCrop()
            ? $image->cover($size->width(), $size->width())
            : $image->scaleDown(width: $size->width());

        return $this->storage->storeThumbnail(
            $media->hash_id,
            $size,
            $image->toWebp(quality: 85)->toString(),
        );
    }

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, config('trove.media.allowed_mimes'), true);
    }
}
