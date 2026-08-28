<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Unit;

use Modules\Tag\DataObjects\TagName;
use Modules\Tag\Exceptions\InvalidTagName;
use Tests\TestCase;

final class TagNameTest extends TestCase
{
    public function test_it_normalizes_case_and_whitespace(): void
    {
        $this->assertSame('long_cat', TagName::from('  Long   Cat ')->value);
    }

    public function test_it_preserves_unicode(): void
    {
        $this->assertSame('hőség', TagName::from('Hőség')->value);
    }

    public function test_it_rejects_the_namespace_separator(): void
    {
        $this->expectException(InvalidTagName::class);

        TagName::from('artist:foo');
    }

    public function test_it_rejects_a_leading_exclusion_operator(): void
    {
        $this->expectException(InvalidTagName::class);

        TagName::from('-politics');
    }

    public function test_it_rejects_reserved_words(): void
    {
        $this->expectException(InvalidTagName::class);

        TagName::from('autocomplete');
    }

    public function test_it_rejects_an_empty_name(): void
    {
        $this->expectException(InvalidTagName::class);

        TagName::from('   ');
    }

    public function test_try_from_returns_null_instead_of_throwing(): void
    {
        $this->assertNull(TagName::tryFrom('artist:foo'));
        $this->assertSame('cat', TagName::tryFrom('Cat')?->value);
    }

    public function test_the_exception_names_the_offending_character(): void
    {
        try {
            TagName::from('a/b');
            $this->fail('Expected InvalidTagName.');
        } catch (InvalidTagName $e) {
            $this->assertSame('/', $e->replacements['character']);
        }
    }
}
