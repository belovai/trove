<?php

declare(strict_types=1);

namespace Modules\Media\DataObjects;

final readonly class ExtractedMetadata
{
    public function __construct(
        public int $width,
        public int $height,
        public bool $isAnimated,
        public ?int $frameCount,
        public string $dominantColor,
    ) {}
}
