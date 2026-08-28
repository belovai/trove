<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Media\Models\Media;
use Modules\Tag\Actions\CreateImplication;
use Modules\Tag\Actions\SyncMediaTags;
use Modules\Tag\Enums\TagSource;
use Modules\Tag\Models\Tag;
use Tests\TestCase;

final class SyncMediaTagsTest extends TestCase
{
    use RefreshDatabase;

    private SyncMediaTags $sync;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sync = app(SyncMediaTags::class);
    }

    /**
     * @return array<string, string> tag name => source
     */
    private function rowsFor(Media $media): array
    {
        return DB::table('media_tag')
            ->join('tags', 'tags.id', '=', 'media_tag.tag_id')
            ->where('media_tag.media_id', $media->id)
            ->pluck('media_tag.source', 'tags.name')
            ->all();
    }

    public function test_it_writes_human_rows_and_the_implied_closure(): void
    {
        $media = Media::factory()->create();
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        $animal = Tag::factory()->create(['name' => 'animal']);
        app(CreateImplication::class)->handle($calico, $cat);
        app(CreateImplication::class)->handle($cat, $animal);

        $this->sync->handle($media, [$calico->id], null);

        $this->assertSame([
            'calico' => TagSource::Human->value,
            'cat' => TagSource::Implied->value,
            'animal' => TagSource::Implied->value,
        ], $this->rowsFor($media));
    }

    public function test_a_human_tag_that_is_also_implied_stays_human(): void
    {
        $media = Media::factory()->create();
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateImplication::class)->handle($calico, $cat);

        $this->sync->handle($media, [$calico->id, $cat->id], null);

        $this->assertSame(TagSource::Human->value, $this->rowsFor($media)['cat']);
    }

    public function test_removing_a_tag_keeps_implied_tags_reachable_by_another_path(): void
    {
        $media = Media::factory()->create();
        $calico = Tag::factory()->create(['name' => 'calico']);
        $tabby = Tag::factory()->create(['name' => 'tabby']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateImplication::class)->handle($calico, $cat);
        app(CreateImplication::class)->handle($tabby, $cat);

        $this->sync->handle($media, [$calico->id, $tabby->id], null);
        $this->sync->handle($media, [$tabby->id], null);

        $rows = $this->rowsFor($media);
        $this->assertArrayNotHasKey('calico', $rows);
        $this->assertSame(TagSource::Implied->value, $rows['cat']);
    }

    public function test_removing_the_last_implying_tag_removes_the_implied_tag(): void
    {
        $media = Media::factory()->create();
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateImplication::class)->handle($calico, $cat);

        $this->sync->handle($media, [$calico->id], null);
        $this->sync->handle($media, [], null);

        $this->assertSame([], $this->rowsFor($media));
    }

    public function test_a_tag_typed_by_hand_survives_removal_of_the_tag_that_implied_it(): void
    {
        $media = Media::factory()->create();
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateImplication::class)->handle($calico, $cat);

        $this->sync->handle($media, [$calico->id, $cat->id], null);
        $this->sync->handle($media, [$cat->id], null);

        $this->assertSame(['cat' => TagSource::Human->value], $this->rowsFor($media));
    }

    public function test_usage_and_tag_counts_count_human_rows_only(): void
    {
        $media = Media::factory()->create();
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateImplication::class)->handle($calico, $cat);

        $this->sync->handle($media, [$calico->id], null);

        $this->assertSame(1, $calico->fresh()->usage_count);
        $this->assertSame(0, $cat->fresh()->usage_count);
        $this->assertSame(1, $media->fresh()->tag_count);
    }
}
