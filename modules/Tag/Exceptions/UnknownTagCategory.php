<?php

declare(strict_types=1);

namespace Modules\Tag\Exceptions;

use DomainException;

/**
 * A `category:` prefix naming a category that does not exist. Deliberately an
 * error rather than a silent fallback to the default: a typo in the prefix
 * would otherwise file the tag in the wrong place with no signal.
 */
final class UnknownTagCategory extends DomainException
{
    /**
     * @param  array<string, string>  $replacements
     */
    private function __construct(
        public readonly string $messageKey,
        public readonly array $replacements = [],
    ) {
        parent::__construct($messageKey);
    }

    public static function named(string $category): self
    {
        return new self('tag::validation.unknown_category', ['category' => $category]);
    }

    public function translated(): string
    {
        return __($this->messageKey, $this->replacements);
    }
}
