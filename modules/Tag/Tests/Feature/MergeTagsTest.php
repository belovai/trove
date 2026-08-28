<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Media;
use Modules\Tag\Actions\CreateAlias;
use Modules\Tag\Actions\CreateImplication;
use Modules\Tag\Actions\DeleteTag;
use Modules\Tag\Actions\MergeTags;
use Modules\Tag\Actions\SyncMediaTags;
use Modules\Tag\Enums\TagSource;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagAlias;
use Tests\TestCase;

final class MergeTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_moves_pivot_rows_to_the_target(): void
    {
        $media = Media::factory()->create();
        $kitty = Tag::factory()->create(['name' => 'kitty']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(SyncMediaTags::class)->handle($media, [$kitty->id], null);

        app(MergeTags::class)->handle($kitty, $cat);

        $this->assertSame(['cat'], $media->tags()->pluck('name')->all());
        $this->assertSame(1, $cat->fresh()->usage_count);
        $this->assertNull(Tag::query()->find($kitty->id));
    }

    public function test_it_collapses_an_item_carrying_both_tags(): void
    {
        $media = Media::factory()->create();
        $kitty = Tag::factory()->create(['name' => 'kitty']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(SyncMediaTags::class)->handle($media, [$kitty->id, $cat->id], null);

        app(MergeTags::class)->handle($kitty, $cat);

        $this->assertSame(['cat'], $media->tags()->pluck('name')->all());
        $this->assertSame(1, $media->fresh()->tag_count);
    }

    public function test_it_leaves_an_alias_behind_and_repoints_existing_ones(): void
    {
        $kitty = Tag::factory()->create(['name' => 'kitty']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateAlias::class)->handle($kitty, 'kitteh');

        app(MergeTags::class)->handle($kitty, $cat);

        $this->assertSame(
            ['kitteh', 'kitty'],
            TagAlias::query()->orderBy('alias_name')->pluck('alias_name')->all(),
        );
        $this->assertSame([$cat->id, $cat->id], TagAlias::query()->orderBy('alias_name')->pluck('tag_id')->all());
    }

    public function test_it_migrates_implications(): void
    {
        $kitty = Tag::factory()->create(['name' => 'kitty']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        $animal = Tag::factory()->create(['name' => 'animal']);
        app(CreateImplication::class)->handle($kitty, $animal);

        app(MergeTags::class)->handle($kitty, $cat);

        $this->assertTrue($cat->implications()->where('implied_tag_id', $animal->id)->exists());
    }

    public function test_deleting_a_tag_reruns_the_closure_for_its_items(): void
    {
        $media = Media::factory()->create();
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateImplication::class)->handle($calico, $cat);
        app(SyncMediaTags::class)->handle($media, [$calico->id], null);

        app(DeleteTag::class)->handle($calico);

        $this->assertSame([], $media->tags()->pluck('name')->all());
        $this->assertSame(0, $media->fresh()->tag_count);
    }

    public function test_deleting_a_tag_keeps_items_tagged_by_other_paths(): void
    {
        $media = Media::factory()->create();
        $calico = Tag::factory()->create(['name' => 'calico']);
        $tabby = Tag::factory()->create(['name' => 'tabby']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateImplication::class)->handle($calico, $cat);
        app(CreateImplication::class)->handle($tabby, $cat);
        app(SyncMediaTags::class)->handle($media, [$calico->id, $tabby->id], null);

        app(DeleteTag::class)->handle($calico);

        $this->assertSame(
            TagSource::Implied->value,
            $media->tags()->where('name', 'cat')->firstOrFail()->pivot->source,
        );
    }
}
