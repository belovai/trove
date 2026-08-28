<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Media;
use Modules\Tag\Actions\SyncMediaTags;
use Modules\Tag\Models\Tag;
use Modules\Tag\Services\CoOccurrenceQuery;
use Tests\TestCase;

final class CoOccurrenceQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_ranks_a_tight_companion_above_a_ubiquitous_one(): void
    {
        $cat = Tag::factory()->create(['name' => 'cat']);
        $whiskers = Tag::factory()->create(['name' => 'whiskers']);
        $meme = Tag::factory()->create(['name' => 'meme']);

        $sync = app(SyncMediaTags::class);

        // whiskers appears only with cat; meme appears everywhere.
        foreach (range(1, 3) as $ignored) {
            $sync->handle(Media::factory()->create(), [$cat->id, $whiskers->id, $meme->id], null);
        }

        foreach (range(1, 20) as $ignored) {
            $sync->handle(Media::factory()->create(), [$meme->id], null);
        }

        $related = app(CoOccurrenceQuery::class)->relatedTo($cat);

        $this->assertSame('whiskers', $related[0]->name);
        $this->assertSame('meme', $related[1]->name);
        $this->assertGreaterThan($related[1]->score, $related[0]->score);
    }

    public function test_it_ignores_pairs_below_the_support_threshold(): void
    {
        $cat = Tag::factory()->create(['name' => 'cat']);
        $fluke = Tag::factory()->create(['name' => 'fluke']);

        app(SyncMediaTags::class)->handle(Media::factory()->create(), [$cat->id, $fluke->id], null);

        $this->assertSame([], app(CoOccurrenceQuery::class)->relatedTo($cat));
    }

    public function test_a_tag_is_never_related_to_itself(): void
    {
        $cat = Tag::factory()->create(['name' => 'cat']);
        $sync = app(SyncMediaTags::class);

        foreach (range(1, 3) as $ignored) {
            $sync->handle(Media::factory()->create(), [$cat->id], null);
        }

        $this->assertSame([], app(CoOccurrenceQuery::class)->relatedTo($cat));
    }
}
