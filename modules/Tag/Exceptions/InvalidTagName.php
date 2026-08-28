<?php

declare(strict_types=1);

namespace Modules\Tag\Exceptions;

use InvalidArgumentException;

/**
 * A rejected tag name. Carries a translation key rather than a message so the
 * validation layer can render it in the viewer's locale.
 */
final class InvalidTagName extends InvalidArgumentException
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

    public static function empty(): self
    {
        return new self('tag::validation.tag_name_empty');
    }

    public static function reservedCharacter(string $character): self
    {
        return new self('tag::validation.tag_name_reserved_character', ['character' => $character]);
    }

    public static function reservedPrefix(string $character): self
    {
        return new self('tag::validation.tag_name_reserved_prefix', ['character' => $character]);
    }

    public static function reservedWord(string $word): self
    {
        return new self('tag::validation.tag_name_reserved_word', ['word' => $word]);
    }

    public function translated(): string
    {
        return __($this->messageKey, $this->replacements);
    }
}
