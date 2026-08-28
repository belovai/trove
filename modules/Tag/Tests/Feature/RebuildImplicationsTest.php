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

final class RebuildImplicationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_materializes_implications_added_after_tagging(): void
    {
        $media = Media::factory()->create();
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);

        app(SyncMediaTags::class)->handle($media, [$calico->id], null);
        app(CreateImplication::class)->handle($calico, $cat);

        $this->assertFalse($media->tags()->where('name', 'cat')->exists());

        $this->artisan('trove:rebuild-implications')->assertSuccessful();

        $this->assertSame(
            TagSource::Implied->value,
            $media->tags()->where('name', 'cat')->firstOrFail()->pivot->source,
        );
    }

    public function test_it_drops_implied_rows_whose_implication_is_gone(): void
    {
        $media = Media::factory()->create();
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateImplication::class)->handle($calico, $cat);
        app(SyncMediaTags::class)->handle($media, [$calico->id], null);

        DB::table('tag_implications')->delete();

        $this->artisan('trove:rebuild-implications')->assertSuccessful();

        $this->assertFalse($media->tags()->where('name', 'cat')->exists());
    }

    public function test_it_never_touches_human_rows(): void
    {
        $media = Media::factory()->create();
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(SyncMediaTags::class)->handle($media, [$cat->id], null);

        $this->artisan('trove:rebuild-implications')->assertSuccessful();

        $this->assertSame(TagSource::Human->value, $media->tags()->firstOrFail()->pivot->source);
        $this->assertSame(1, $cat->fresh()->usage_count);
    }
}
