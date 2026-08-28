<?php

declare(strict_types=1);

namespace Modules\Tag\DataObjects;

use Modules\Tag\Exceptions\InvalidTagName;
use Stringable;

/**
 * The only place a tag name is normalized or validated. Every name entering
 * the system — user input, taxonomy import, seeder, test — goes through here.
 */
final readonly class TagName implements Stringable
{
    /**
     * Names that are route segments under /tags/ or search query keywords. A
     * tag carrying one of these is unaddressable, so it is refused at creation.
     */
    public const RESERVED_WORDS = [
        'autocomplete', 'sort', 'tags', 'user', 'id', 'order', 'date', 'safety', 'visibility',
    ];

    /** Characters that would collide with the input or search grammar. */
    private const RESERVED_CHARACTERS = [':', '*', '/'];

    private function __construct(public string $value) {}

    public static function from(string $raw): self
    {
        $normalized = self::normalize($raw);

        if ($normalized === '') {
            throw InvalidTagName::empty();
        }

        foreach (self::RESERVED_CHARACTERS as $character) {
            if (str_contains($normalized, $character)) {
                throw InvalidTagName::reservedCharacter($character);
            }
        }

        // Only leading: "rock-n-roll" is a perfectly good tag.
        if (str_starts_with($normalized, '-')) {
            throw InvalidTagName::reservedPrefix('-');
        }

        if (in_array($normalized, self::RESERVED_WORDS, true)) {
            throw InvalidTagName::reservedWord($normalized);
        }

        return new self($normalized);
    }

    public static function tryFrom(string $raw): ?self
    {
        try {
            return self::from($raw);
        } catch (InvalidTagName) {
            return null;
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Trim first, so surrounding whitespace never becomes an underscore.
     * Lowercasing is multibyte-aware: "Hőség" must become "hőség", not "hőség"
     * with a mangled vowel.
     */
    private static function normalize(string $raw): string
    {
        return (string) preg_replace('/\s+/u', '_', mb_strtolower(trim($raw)));
    }
}
