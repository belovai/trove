<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Media;
use Modules\Tag\Enums\TagSource;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;
use Tests\TestCase;

final class TagSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_default_category_exists_after_migration(): void
    {
        $default = TagCategory::default();

        $this->assertSame('general', $default->name);
        $this->assertTrue($default->is_default);
    }

    public function test_tag_names_are_unique(): void
    {
        Tag::factory()->create(['name' => 'cat']);

        $this->expectException(QueryException::class);

        Tag::factory()->create(['name' => 'cat']);
    }

    public function test_a_tag_belongs_to_a_category(): void
    {
        $category = TagCategory::factory()->create(['name' => 'character']);
        $tag = Tag::factory()->for($category, 'category')->create();

        $this->assertSame('character', $tag->category?->name);
    }

    public function test_the_pivot_holds_a_source_and_rejects_duplicates(): void
    {
        $media = Media::factory()->create();
        $tag = Tag::factory()->create();

        $media->tags()->attach($tag->id, ['source' => TagSource::Human->value, 'created_at' => now()]);

        $this->expectException(QueryException::class);

        $media->tags()->attach($tag->id, ['source' => TagSource::Implied->value, 'created_at' => now()]);
    }

    public function test_a_tag_is_routed_by_its_name(): void
    {
        $this->assertSame('name', (new Tag)->getRouteKeyName());
    }
}
