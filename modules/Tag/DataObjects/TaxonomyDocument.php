<?php

declare(strict_types=1);

namespace Modules\Tag\DataObjects;

/**
 * The interchange format from ARCHITECTURE.v2.md §5.5. It is both the
 * migration path from an existing booru and the reason the repository ships
 * no opinionated default taxonomy: community taxonomies are JSON files, not
 * migrations.
 */
final readonly class TaxonomyDocument
{
    public const VERSION = 1;

    /**
     * @param  list<array{name: string, color: string, sort_order: int}>  $categories
     * @param  list<array{name: string, category: ?string, description: ?string}>  $tags
     * @param  list<array{alias: string, tag: string}>  $aliases
     * @param  list<array{tag: string, implies: string}>  $implications
     */
    public function __construct(
        public array $categories,
        public array $tags,
        public array $aliases,
        public array $implications,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            categories: array_values((array) ($raw['categories'] ?? [])),
            tags: array_values((array) ($raw['tags'] ?? [])),
            aliases: array_values((array) ($raw['aliases'] ?? [])),
            implications: array_values((array) ($raw['implications'] ?? [])),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => self::VERSION,
            'categories' => $this->categories,
            'tags' => $this->tags,
            'aliases' => $this->aliases,
            'implications' => $this->implications,
        ];
    }
}
