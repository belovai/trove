<?php

declare(strict_types=1);

namespace Modules\Tag\DataObjects;

final readonly class ImplicationCandidate
{
    public function __construct(
        public int $fromId,
        public string $fromName,
        public int $toId,
        public string $toName,
        public float $confidence,
    ) {}
}
