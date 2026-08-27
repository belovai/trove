<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

use Modules\Media\Enums\ThumbnailSize;
use Modules\Media\Models\Media;

interface ThumbnailGenerator
{
    /**
     * Generates one size and stores it. Returns the storage path.
     */
    public function generate(Media $media, ThumbnailSize $size): string;

    public function supports(string $mimeType): bool;
}
