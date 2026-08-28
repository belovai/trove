<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tag\Actions\CreateAlias;
use Modules\Tag\Actions\CreateImplication;
use Modules\Tag\Actions\DeleteImplication;
use Modules\Tag\Exceptions\InvalidTaxonomyEdge;
use Modules\Tag\Models\Tag;
use Tests\TestCase;

final class TaxonomyEdgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_alias_is_normalized_like_a_tag_name(): void
    {
        $cat = Tag::factory()->create(['name' => 'cat']);

        $alias = app(CreateAlias::class)->handle($cat, '  Kitty  ');

        $this->assertSame('kitty', $alias->alias_name);
        $this->assertSame($cat->id, $alias->tag_id);
    }

    public function test_an_alias_may_not_collide_with_an_existing_tag(): void
    {
        $cat = Tag::factory()->create(['name' => 'cat']);
        Tag::factory()->create(['name' => 'dog']);

        $this->expectException(InvalidTaxonomyEdge::class);

        app(CreateAlias::class)->handle($cat, 'dog');
    }

    public function test_an_alias_may_not_be_reused_for_another_tag(): void
    {
        $cat = Tag::factory()->create(['name' => 'cat']);
        $dog = Tag::factory()->create(['name' => 'dog']);
        app(CreateAlias::class)->handle($cat, 'kitty');

        $this->expectException(InvalidTaxonomyEdge::class);

        app(CreateAlias::class)->handle($dog, 'kitty');
    }

    public function test_an_implication_is_created(): void
    {
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);

        app(CreateImplication::class)->handle($calico, $cat);

        $this->assertTrue($calico->implications()->where('implied_tag_id', $cat->id)->exists());
    }

    public function test_a_self_implication_is_rejected(): void
    {
        $cat = Tag::factory()->create(['name' => 'cat']);

        $this->expectException(InvalidTaxonomyEdge::class);

        app(CreateImplication::class)->handle($cat, $cat);
    }

    public function test_a_cycle_is_rejected(): void
    {
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        $animal = Tag::factory()->create(['name' => 'animal']);

        app(CreateImplication::class)->handle($calico, $cat);
        app(CreateImplication::class)->handle($cat, $animal);

        $this->expectException(InvalidTaxonomyEdge::class);

        app(CreateImplication::class)->handle($animal, $calico);
    }

    public function test_a_duplicate_implication_is_rejected(): void
    {
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);

        app(CreateImplication::class)->handle($calico, $cat);

        $this->expectException(InvalidTaxonomyEdge::class);

        app(CreateImplication::class)->handle($calico, $cat);
    }

    public function test_an_implication_can_be_deleted(): void
    {
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateImplication::class)->handle($calico, $cat);

        app(DeleteImplication::class)->handle($calico, $cat);

        $this->assertFalse($calico->implications()->where('implied_tag_id', $cat->id)->exists());
    }
}
