<?php

declare(strict_types=1);

namespace Modules\Tag\DataObjects;

final readonly class DuplicateCandidate
{
    public function __construct(
        public int $leftId,
        public string $left,
        public int $rightId,
        public string $right,
        public int $distance,
    ) {}
}
