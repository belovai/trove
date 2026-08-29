<?php

declare(strict_types=1);

namespace Modules\Media\DataObjects;

use Modules\Media\Enums\SafetyRating;

/**
 * The listing filters a viewer actually got: their ad-hoc selection when they
 * sent one, their stored default expanded to a set when they did not. Every
 * page that lists media resolves them the same way and echoes them back, so
 * the filter bar can render the state that applied.
 */
final readonly class BrowseFilters
{
    /**
     * @param  list<SafetyRating>  $ratings
     */
    public function __construct(
        public array $ratings,
        public bool $untagged,
        public bool $unlisted,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'safety' => array_map(fn (SafetyRating $rating): string => $rating->value, $this->ratings),
            'untagged' => $this->untagged,
            'unlisted' => $this->unlisted,
        ];
    }
}
