<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Favorite;
use Modules\Media\Models\Media;
use Modules\User\Models\User;
use Tests\TestCase;

final class FavoriteModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_favorite_per_user_per_item(): void
    {
        $media = Media::factory()->create();
        $user = User::factory()->create();

        Favorite::query()->create(['media_id' => $media->id, 'user_id' => $user->id]);

        $this->expectException(QueryException::class);

        Favorite::query()->create(['media_id' => $media->id, 'user_id' => $user->id]);
    }

    public function test_hard_deleting_the_item_takes_its_favorites(): void
    {
        $media = Media::factory()->create();
        $user = User::factory()->create();

        Favorite::query()->create(['media_id' => $media->id, 'user_id' => $user->id]);

        $media->forceDelete();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_soft_deleting_the_item_keeps_its_favorites(): void
    {
        $media = Media::factory()->create();
        $user = User::factory()->create();

        Favorite::query()->create(['media_id' => $media->id, 'user_id' => $user->id]);

        $media->delete();

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_deleting_the_user_takes_their_favorites(): void
    {
        $media = Media::factory()->create();
        $user = User::factory()->create();

        Favorite::query()->create(['media_id' => $media->id, 'user_id' => $user->id]);

        $user->forceDelete();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_is_favorited_by_reflects_only_that_viewer(): void
    {
        $media = Media::factory()->create();
        $mine = User::factory()->create();
        $theirs = User::factory()->create();

        Favorite::query()->create(['media_id' => $media->id, 'user_id' => $mine->id]);

        $this->assertTrue($media->isFavoritedBy($mine));
        $this->assertFalse($media->isFavoritedBy($theirs));
        $this->assertFalse($media->isFavoritedBy(null));
    }

    public function test_favorited_by_scope_lists_only_that_users_items(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();

        $myFavorite = Media::factory()->create();
        $theirFavorite = Media::factory()->create();
        Media::factory()->create(); // favorited by nobody

        Favorite::query()->create(['media_id' => $myFavorite->id, 'user_id' => $mine->id]);
        Favorite::query()->create(['media_id' => $theirFavorite->id, 'user_id' => $theirs->id]);

        $result = Media::query()->favoritedBy($mine)->get();

        $this->assertCount(1, $result);
        $this->assertSame($myFavorite->id, $result->first()->id);
    }
}
