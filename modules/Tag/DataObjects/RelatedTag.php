<?php

declare(strict_types=1);

namespace Modules\Tag\DataObjects;

final readonly class RelatedTag
{
    public function __construct(
        public int $tagId,
        public string $name,
        public ?string $category,
        public ?string $color,
        public int $shared,
        public float $score,
    ) {}
}
