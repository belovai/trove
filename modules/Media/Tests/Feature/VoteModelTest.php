<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Enums\VoteValue;
use Modules\Media\Models\Media;
use Modules\Media\Models\Vote;
use Modules\User\Models\User;
use Tests\TestCase;

final class VoteModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_media_item_starts_at_zero(): void
    {
        $this->assertSame(0, Media::factory()->create()->score);
    }

    public function test_the_value_is_cast_to_the_enum(): void
    {
        $media = Media::factory()->create();
        $user = User::factory()->create();

        Vote::query()->create([
            'media_id' => $media->id,
            'user_id' => $user->id,
            'value' => VoteValue::Down,
        ]);

        $this->assertSame(VoteValue::Down, Vote::query()->firstOrFail()->value);
    }

    public function test_vote_of_returns_only_that_viewers_own_vote(): void
    {
        $media = Media::factory()->create();
        $mine = User::factory()->create();
        $theirs = User::factory()->create();

        Vote::query()->create(['media_id' => $media->id, 'user_id' => $mine->id, 'value' => VoteValue::Up]);
        Vote::query()->create(['media_id' => $media->id, 'user_id' => $theirs->id, 'value' => VoteValue::Down]);

        $this->assertSame(VoteValue::Up, $media->voteOf($mine));
        $this->assertNull($media->voteOf(null));
        $this->assertNull($media->voteOf(User::factory()->create()));
    }

    public function test_one_vote_per_user_per_item(): void
    {
        $media = Media::factory()->create();
        $user = User::factory()->create();

        Vote::query()->create(['media_id' => $media->id, 'user_id' => $user->id, 'value' => VoteValue::Up]);

        $this->expectException(QueryException::class);

        Vote::query()->create(['media_id' => $media->id, 'user_id' => $user->id, 'value' => VoteValue::Down]);
    }

    public function test_hard_deleting_the_item_takes_its_votes(): void
    {
        $media = Media::factory()->create();
        $user = User::factory()->create();

        Vote::query()->create(['media_id' => $media->id, 'user_id' => $user->id, 'value' => VoteValue::Up]);

        $media->forceDelete();

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_soft_deleting_the_item_keeps_its_votes(): void
    {
        $media = Media::factory()->create();
        $user = User::factory()->create();

        Vote::query()->create(['media_id' => $media->id, 'user_id' => $user->id, 'value' => VoteValue::Up]);

        $media->delete();

        $this->assertDatabaseCount('votes', 1);
    }
}
