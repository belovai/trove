<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Media;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class VotePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_regular_user_may_vote_on_someone_elses_item(): void
    {
        $user = User::factory()->create(['rank' => UserRank::Regular]);

        $this->assertTrue($user->can('vote', Media::factory()->create()));
    }

    public function test_a_restricted_user_may_not_vote(): void
    {
        $user = User::factory()->create(['rank' => UserRank::Restricted]);

        $this->assertFalse($user->can('vote', Media::factory()->create()));
    }

    public function test_nobody_may_vote_on_their_own_upload(): void
    {
        $user = User::factory()->create(['rank' => UserRank::Administrator]);
        $media = Media::factory()->for($user, 'uploader')->create();

        $this->assertFalse($user->can('vote', $media));
    }

    public function test_the_rule_holds_for_an_anonymous_upload_too(): void
    {
        $user = User::factory()->create(['rank' => UserRank::Regular]);
        $media = Media::factory()->for($user, 'uploader')->create(['is_anonymous' => true]);

        $this->assertFalse($user->can('vote', $media));
    }

    public function test_an_anonymous_item_is_votable_by_everyone_else(): void
    {
        $user = User::factory()->create(['rank' => UserRank::Regular]);

        $this->assertTrue($user->can('vote', Media::factory()->create(['is_anonymous' => true])));
    }

    public function test_an_item_the_viewer_cannot_see_is_not_votable(): void
    {
        $user = User::factory()->create(['rank' => UserRank::Regular]);

        $this->assertFalse($user->can('vote', Media::factory()->private()->create()));
    }

    public function test_a_banned_user_may_not_vote(): void
    {
        $user = User::factory()->create(['rank' => UserRank::Regular, 'banned_at' => now()]);

        $this->assertFalse($user->can('vote', Media::factory()->create()));
    }
}
