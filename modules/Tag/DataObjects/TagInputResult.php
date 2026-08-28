<?php

declare(strict_types=1);

namespace Modules\Tag\DataObjects;

final readonly class TagInputResult
{
    /**
     * @param  list<int>  $tagIds  canonical tag ids, deduplicated
     * @param  list<string>  $warnings  already-translated, shown inline by TagInput
     */
    public function __construct(
        public array $tagIds,
        public array $warnings,
    ) {}
}
