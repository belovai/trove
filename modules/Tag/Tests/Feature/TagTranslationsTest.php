<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Tests\TestCase;

final class TagTranslationsTest extends TestCase
{
    public function test_every_english_key_has_a_hungarian_counterpart(): void
    {
        foreach (['tag', 'validation'] as $file) {
            $en = trans("tag::{$file}", [], 'en');
            $hu = trans("tag::{$file}", [], 'hu');

            $this->assertIsArray($en);
            $this->assertIsArray($hu);
            $this->assertSame(array_keys($en), array_keys($hu), "tag::{$file} keys differ");
        }
    }

    public function test_a_rejected_tag_name_renders_a_real_message(): void
    {
        $message = trans('tag::validation.tag_name_reserved_character', ['character' => ':'], 'en');

        $this->assertStringNotContainsString('tag::', $message);
        $this->assertStringContainsString(':', $message);
    }
}
