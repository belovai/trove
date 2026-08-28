<?php

declare(strict_types=1);

namespace Modules\Tag\Exceptions;

use DomainException;

/**
 * A refused alias or implication. Same shape as InvalidTagName: a translation
 * key, so the message can be rendered in the viewer's locale.
 */
final class InvalidTaxonomyEdge extends DomainException
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

    public static function aliasCollidesWithTag(string $name): self
    {
        return new self('tag::validation.alias_collides_with_tag', ['name' => $name]);
    }

    public static function aliasAlreadyTaken(string $name): self
    {
        return new self('tag::validation.alias_already_taken', ['name' => $name]);
    }

    public static function selfImplication(string $name): self
    {
        return new self('tag::validation.self_implication', ['name' => $name]);
    }

    public static function implicationCycle(string $from, string $to): self
    {
        return new self('tag::validation.implication_cycle', ['from' => $from, 'to' => $to]);
    }

    public static function implicationExists(string $from, string $to): self
    {
        return new self('tag::validation.implication_exists', ['from' => $from, 'to' => $to]);
    }

    public function translated(): string
    {
        return __($this->messageKey, $this->replacements);
    }
}
