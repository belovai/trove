<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Media\Models\Favorite;
use Modules\Media\Models\Media;
use Modules\User\Models\User;
use Tests\TestCase;

final class FavoritesIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get('/favorites')->assertRedirect('/login');
    }

    public function test_it_lists_only_the_viewers_own_favorites(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $mine = Media::factory()->create();
        $theirs = Media::factory()->create();

        Favorite::query()->create(['media_id' => $mine->id, 'user_id' => $viewer->id]);
        Favorite::query()->create(['media_id' => $theirs->id, 'user_id' => $other->id]);

        $this->actingAs($viewer)
            ->get('/favorites')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('favorites/Index')
                ->has('media.data', 1)
                ->where('media.data.0.hash_id', $mine->hash_id));
    }

    public function test_the_safety_filter_applies(): void
    {
        $viewer = User::factory()->create();
        $unsafe = Media::factory()->unsafe()->create();
        Favorite::query()->create(['media_id' => $unsafe->id, 'user_id' => $viewer->id]);

        $this->actingAs($viewer)
            ->get('/favorites')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('media.data', 0));
    }

    public function test_the_sort_parameter_is_respected(): void
    {
        $viewer = User::factory()->create();
        $low = Media::factory()->create(['score' => -1]);
        $high = Media::factory()->create(['score' => 5]);
        Favorite::query()->create(['media_id' => $low->id, 'user_id' => $viewer->id]);
        Favorite::query()->create(['media_id' => $high->id, 'user_id' => $viewer->id]);

        $this->actingAs($viewer)
            ->get('/favorites?sort=score')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('media.data.0.hash_id', $high->hash_id)
                ->where('media.data.1.hash_id', $low->hash_id));
    }

    public function test_a_soft_deleted_favorited_item_disappears_for_everyone_including_the_owner(): void
    {
        $uploader = User::factory()->create();
        $item = Media::factory()->for($uploader, 'uploader')->create();
        Favorite::query()->create(['media_id' => $item->id, 'user_id' => $uploader->id]);
        $item->delete();

        $this->actingAs($uploader)
            ->get('/favorites')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('media.data', 0));
    }
}
