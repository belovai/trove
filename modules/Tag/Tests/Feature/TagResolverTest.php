<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tag\Actions\CreateAlias;
use Modules\Tag\Exceptions\UnknownTagCategory;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;
use Modules\Tag\Services\TagResolver;
use Tests\TestCase;

final class TagResolverTest extends TestCase
{
    use RefreshDatabase;

    private TagResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(TagResolver::class);
    }

    public function test_it_creates_unknown_tags_in_the_default_category(): void
    {
        $result = $this->resolver->resolve(['Long Cat']);

        $tag = Tag::query()->firstOrFail();

        $this->assertSame([$tag->id], $result->tagIds);
        $this->assertSame('long_cat', $tag->name);
        $this->assertSame(TagCategory::default()->id, $tag->category_id);
        $this->assertSame([], $result->warnings);
    }

    public function test_a_namespace_prefix_sets_the_category_of_a_new_tag(): void
    {
        $artist = TagCategory::factory()->create(['name' => 'artist']);

        $this->resolver->resolve(['artist:john_wick']);

        $this->assertSame($artist->id, Tag::query()->where('name', 'john_wick')->firstOrFail()->category_id);
    }

    public function test_a_namespace_prefix_is_ignored_for_an_existing_tag_and_warns(): void
    {
        $character = TagCategory::factory()->create(['name' => 'character']);
        TagCategory::factory()->create(['name' => 'artist']);
        $existing = Tag::factory()->for($character, 'category')->create(['name' => 'john_wick']);

        $result = $this->resolver->resolve(['artist:john_wick']);

        $this->assertSame([$existing->id], $result->tagIds);
        $this->assertSame($character->id, $existing->fresh()->category_id);
        $this->assertCount(1, $result->warnings);
    }

    public function test_a_matching_prefix_on_an_existing_tag_does_not_warn(): void
    {
        $character = TagCategory::factory()->create(['name' => 'character']);
        Tag::factory()->for($character, 'category')->create(['name' => 'john_wick']);

        $result = $this->resolver->resolve(['character:john_wick']);

        $this->assertSame([], $result->warnings);
    }

    public function test_an_unknown_category_prefix_is_an_error(): void
    {
        $this->expectException(UnknownTagCategory::class);

        $this->resolver->resolve(['nosuchcategory:foo']);
    }

    public function test_an_alias_resolves_to_its_canonical_tag(): void
    {
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateAlias::class)->handle($cat, 'kitty');

        $result = $this->resolver->resolve(['kitty']);

        $this->assertSame([$cat->id], $result->tagIds);
        $this->assertSame(1, Tag::query()->count());
    }

    public function test_repeated_names_resolve_once(): void
    {
        $result = $this->resolver->resolve(['cat', 'Cat', '  cat ']);

        $this->assertCount(1, $result->tagIds);
    }
}
