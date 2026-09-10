<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Actions\CastVote;
use Modules\Media\Enums\VoteValue;
use Modules\Media\Models\Media;
use Modules\User\Models\User;
use Tests\TestCase;

final class CastVoteTest extends TestCase
{
    use RefreshDatabase;

    private function action(): CastVote
    {
        return app(CastVote::class);
    }

    public function test_a_first_vote_inserts_a_row_and_moves_the_score(): void
    {
        $media = Media::factory()->create();
        $user = User::factory()->create();

        $this->assertSame(1, $this->action()->handle($media, $user, VoteValue::Up));
        $this->assertDatabaseHas('votes', ['media_id' => $media->id, 'user_id' => $user->id, 'value' => 1]);
    }

    public function test_switching_direction_updates_in_place_and_moves_the_score_by_two(): void
    {
        $media = Media::factory()->create();
        $user = User::factory()->create();

        $this->action()->handle($media, $user, VoteValue::Up);

        $this->assertSame(-1, $this->action()->handle($media, $user, VoteValue::Down));
        $this->assertDatabaseCount('votes', 1);
    }

    public function test_null_withdraws_the_vote(): void
    {
        $media = Media::factory()->create();
        $user = User::factory()->create();

        $this->action()->handle($media, $user, VoteValue::Up);

        $this->assertSame(0, $this->action()->handle($media, $user, null));
        $this->assertDatabaseCount('votes', 0);
    }

    public function test_withdrawing_without_a_vote_is_harmless(): void
    {
        $media = Media::factory()->create();

        $this->assertSame(0, $this->action()->handle($media, User::factory()->create(), null));
    }

    public function test_the_same_direction_twice_is_idempotent(): void
    {
        $media = Media::factory()->create();
        $user = User::factory()->create();

        $this->action()->handle($media, $user, VoteValue::Up);

        $this->assertSame(1, $this->action()->handle($media, $user, VoteValue::Up));
        $this->assertDatabaseCount('votes', 1);
    }

    public function test_votes_from_different_users_sum(): void
    {
        $media = Media::factory()->create();

        $this->action()->handle($media, User::factory()->create(), VoteValue::Up);
        $this->action()->handle($media, User::factory()->create(), VoteValue::Up);

        $this->assertSame(1, $this->action()->handle($media, User::factory()->create(), VoteValue::Down));
    }
}
