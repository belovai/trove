<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Media;
use Modules\Tag\Actions\CreateImplication;
use Modules\Tag\Actions\SyncMediaTags;
use Modules\Tag\Models\Tag;
use Modules\Tag\Services\TagHealthReport;
use Tests\TestCase;

final class TagHealthReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_unused_tags(): void
    {
        $used = Tag::factory()->create(['name' => 'cat']);
        Tag::factory()->create(['name' => 'orphan']);
        app(SyncMediaTags::class)->handle(Media::factory()->create(), [$used->id], null);

        $this->assertSame(['orphan'], app(TagHealthReport::class)->unused()->pluck('name')->all());
    }

    public function test_it_lists_uncategorized_tags(): void
    {
        Tag::factory()->create(['name' => 'cat']);
        Tag::factory()->create(['name' => 'loose', 'category_id' => null]);

        $this->assertSame(['loose'], app(TagHealthReport::class)->uncategorized()->pluck('name')->all());
    }

    public function test_it_finds_near_duplicate_names(): void
    {
        Tag::factory()->create(['name' => 'colour']);
        Tag::factory()->create(['name' => 'color']);
        Tag::factory()->create(['name' => 'aeroplane']);

        $pairs = app(TagHealthReport::class)->nearDuplicates();

        $this->assertCount(1, $pairs);
        $this->assertSame(['color', 'colour'], [$pairs[0]->left, $pairs[0]->right]);
    }

    public function test_it_suggests_an_implication_when_one_tag_almost_always_accompanies_another(): void
    {
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        $sync = app(SyncMediaTags::class);

        foreach (range(1, 5) as $ignored) {
            $sync->handle(Media::factory()->create(), [$calico->id, $cat->id], null);
        }

        $candidates = app(TagHealthReport::class)->implicationCandidates();

        $this->assertNotSame([], $candidates);
        $this->assertSame('calico', $candidates[0]->fromName);
        $this->assertSame('cat', $candidates[0]->toName);
    }

    public function test_it_does_not_suggest_an_implication_that_already_exists(): void
    {
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateImplication::class)->handle($calico, $cat);
        $sync = app(SyncMediaTags::class);

        foreach (range(1, 5) as $ignored) {
            $sync->handle(Media::factory()->create(), [$calico->id], null);
        }

        $this->assertSame([], app(TagHealthReport::class)->implicationCandidates());
    }
}
