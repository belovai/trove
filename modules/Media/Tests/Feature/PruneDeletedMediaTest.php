<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Tests\TestCase;

final class PruneDeletedMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_removes_old_soft_deleted_items_and_their_files(): void
    {
        Storage::fake('local');
        config(['trove.media.prune_after_days' => 30]);

        $old = Media::factory()->create();
        Storage::disk('local')->put($old->storage_path, 'bytes');
        $old->delete();
        $old->forceFill(['deleted_at' => now()->subDays(31)])->save();

        $this->artisan('media:prune')->assertSuccessful();

        $this->assertSame(0, Media::query()->withTrashed()->count());
        Storage::disk('local')->assertMissing($old->storage_path);
    }

    public function test_it_keeps_recently_deleted_items(): void
    {
        Storage::fake('local');
        config(['trove.media.prune_after_days' => 30]);

        $recent = Media::factory()->create();
        $recent->delete();

        $this->artisan('media:prune')->assertSuccessful();

        $this->assertSame(1, Media::query()->withTrashed()->count());
    }

    public function test_it_never_touches_live_items(): void
    {
        Storage::fake('local');

        $live = Media::factory()->create();

        $this->artisan('media:prune')->assertSuccessful();

        $this->assertSame(1, Media::query()->count());
        $this->assertNotNull($live->fresh());
    }
}
