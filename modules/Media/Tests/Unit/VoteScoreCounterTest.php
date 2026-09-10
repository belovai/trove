<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Enums\VoteValue;
use Modules\Media\Models\Media;
use Modules\Media\Models\Vote;
use Modules\Media\Services\VoteScoreCounter;
use Modules\User\Models\User;
use Tests\TestCase;

final class VoteScoreCounterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sums_the_votes_into_the_column(): void
    {
        $media = Media::factory()->create();

        foreach ([VoteValue::Up, VoteValue::Up, VoteValue::Down] as $value) {
            Vote::query()->create([
                'media_id' => $media->id,
                'user_id' => User::factory()->create()->id,
                'value' => $value,
            ]);
        }

        $score = (new VoteScoreCounter)->recalculate($media);

        $this->assertSame(1, $score);
        $this->assertDatabaseHas('media', ['id' => $media->id, 'score' => 1]);
    }

    public function test_it_updates_the_in_memory_model_without_leaving_it_dirty(): void
    {
        $media = Media::factory()->create();
        Vote::query()->create([
            'media_id' => $media->id,
            'user_id' => User::factory()->create()->id,
            'value' => VoteValue::Up,
        ]);

        (new VoteScoreCounter)->recalculate($media);

        $this->assertSame(1, $media->score);
        $this->assertFalse($media->isDirty());
    }

    public function test_it_goes_negative(): void
    {
        $media = Media::factory()->create();

        foreach ([VoteValue::Down, VoteValue::Down] as $value) {
            Vote::query()->create([
                'media_id' => $media->id,
                'user_id' => User::factory()->create()->id,
                'value' => $value,
            ]);
        }

        $this->assertSame(-2, (new VoteScoreCounter)->recalculate($media));
    }

    public function test_no_votes_means_zero(): void
    {
        $media = Media::factory()->create();
        $media->forceFill(['score' => 7])->save();

        $this->assertSame(0, (new VoteScoreCounter)->recalculate($media->fresh()));
    }

    public function test_a_soft_deleted_item_still_counts_its_votes(): void
    {
        $media = Media::factory()->create();
        Vote::query()->create([
            'media_id' => $media->id,
            'user_id' => User::factory()->create()->id,
            'value' => VoteValue::Up,
        ]);
        $media->delete();

        $this->assertSame(1, (new VoteScoreCounter)->recalculate($media));
    }
}
