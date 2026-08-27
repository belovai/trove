<?php

declare(strict_types=1);

namespace Modules\Media\Enums;

enum ThumbnailSize: string
{
    case Thumb = 'thumb';
    case Preview = 'preview';

    public function width(): int
    {
        return match ($this) {
            self::Thumb => 150,
            self::Preview => 850,
        };
    }

    /**
     * Thumb is a square crop; preview keeps the original aspect ratio.
     */
    public function isSquareCrop(): bool
    {
        return $this === self::Thumb;
    }
}
