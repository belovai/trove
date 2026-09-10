<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Enums\VoteValue;
use Modules\Media\Models\Media;
use Modules\Media\Models\Vote;
use Modules\User\Models\User;
use Tests\TestCase;

final class RebuildMediaScoresTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_repairs_a_drifted_score(): void
    {
        $media = Media::factory()->create();
        Vote::query()->create([
            'media_id' => $media->id,
            'user_id' => User::factory()->create()->id,
            'value' => VoteValue::Up,
        ]);

        // Simulate drift: write the column behind the counter's back.
        $media->forceFill(['score' => 99])->save();

        $this->artisan('media:rebuild-scores')->assertSuccessful();

        $this->assertSame(1, $media->fresh()->score);
    }

    public function test_it_zeroes_an_item_whose_votes_are_gone(): void
    {
        $media = Media::factory()->create();
        $media->forceFill(['score' => 4])->save();

        $this->artisan('media:rebuild-scores')->assertSuccessful();

        $this->assertSame(0, $media->fresh()->score);
    }

    public function test_it_covers_soft_deleted_items(): void
    {
        $media = Media::factory()->create();
        Vote::query()->create([
            'media_id' => $media->id,
            'user_id' => User::factory()->create()->id,
            'value' => VoteValue::Down,
        ]);
        $media->forceFill(['score' => 12])->save();
        $media->delete();

        $this->artisan('media:rebuild-scores')->assertSuccessful();

        $this->assertSame(-1, Media::withTrashed()->findOrFail($media->id)->score);
    }
}
