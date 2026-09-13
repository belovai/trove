<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Media;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class FavoritePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_regular_user_may_favorite_someone_elses_item(): void
    {
        $user = User::factory()->create(['rank' => UserRank::Regular]);

        $this->assertTrue($user->can('favorite', Media::factory()->create()));
    }

    public function test_a_regular_user_may_favorite_their_own_upload(): void
    {
        $user = User::factory()->create(['rank' => UserRank::Regular]);
        $media = Media::factory()->for($user, 'uploader')->create();

        $this->assertTrue($user->can('favorite', $media));
    }

    public function test_a_restricted_user_may_not_favorite(): void
    {
        $user = User::factory()->create(['rank' => UserRank::Restricted]);

        $this->assertFalse($user->can('favorite', Media::factory()->create()));
    }

    public function test_an_item_the_viewer_cannot_see_is_not_favoritable(): void
    {
        $user = User::factory()->create(['rank' => UserRank::Regular]);

        $this->assertFalse($user->can('favorite', Media::factory()->private()->create()));
    }

    public function test_a_banned_user_may_not_favorite(): void
    {
        $user = User::factory()->create(['rank' => UserRank::Regular, 'banned_at' => now()]);

        $this->assertFalse($user->can('favorite', Media::factory()->create()));
    }
}
