<?php

declare(strict_types=1);

namespace Modules\Tag\DataObjects;

/**
 * Splits `category:name` into its parts. The namespace prefix is an input
 * convenience only — the storage model keeps the category in
 * `tags.category_id`, exactly as it would from a dropdown.
 */
final readonly class ParsedTagInput
{
    private function __construct(
        public ?string $category,
        public string $name,
    ) {}

    public static function parse(string $raw): self
    {
        $trimmed = trim($raw);

        // Split on the FIRST colon only. A second one is left in the name,
        // where TagName rejects it — an ambiguous input is not guessed at.
        if (!str_contains($trimmed, ':')) {
            return new self(null, $trimmed);
        }

        [$category, $name] = explode(':', $trimmed, 2);

        return new self(mb_strtolower(trim($category)), $name);
    }
}
