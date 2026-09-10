<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Media;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class FavoriteEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['rank' => UserRank::Regular]);
    }

    public function test_a_guest_is_redirected_to_login_on_store(): void
    {
        $media = Media::factory()->create();

        $this->post("/m/{$media->hash_id}/favorite")->assertRedirect('/login');

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_a_regular_user_can_favorite_an_item(): void
    {
        $media = Media::factory()->create();

        $this->actingAs($this->user())
            ->post("/m/{$media->hash_id}/favorite")
            ->assertRedirect();

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_storing_twice_does_not_duplicate_the_row(): void
    {
        $media = Media::factory()->create();
        $user = $this->user();

        $this->actingAs($user)->post("/m/{$media->hash_id}/favorite");
        $this->actingAs($user)->post("/m/{$media->hash_id}/favorite");

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_a_user_can_favorite_their_own_upload(): void
    {
        $user = $this->user();
        $media = Media::factory()->for($user, 'uploader')->create();

        $this->actingAs($user)
            ->post("/m/{$media->hash_id}/favorite")
            ->assertRedirect();

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_a_restricted_user_is_refused(): void
    {
        $media = Media::factory()->create();

        $this->actingAs(User::factory()->create(['rank' => UserRank::Restricted]))
            ->post("/m/{$media->hash_id}/favorite")
            ->assertForbidden();
    }

    public function test_an_invisible_item_is_not_found(): void
    {
        $media = Media::factory()->private()->create();

        $this->actingAs($this->user())
            ->post("/m/{$media->hash_id}/favorite")
            ->assertNotFound();
    }

    public function test_destroy_removes_the_favorite(): void
    {
        $media = Media::factory()->create();
        $user = $this->user();

        $this->actingAs($user)->post("/m/{$media->hash_id}/favorite");
        $this->actingAs($user)
            ->delete("/m/{$media->hash_id}/favorite")
            ->assertRedirect();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_destroy_is_idempotent(): void
    {
        $media = Media::factory()->create();

        $this->actingAs($this->user())
            ->delete("/m/{$media->hash_id}/favorite")
            ->assertRedirect();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_a_guest_is_redirected_to_login_on_destroy(): void
    {
        $media = Media::factory()->create();

        $this->delete("/m/{$media->hash_id}/favorite")->assertRedirect('/login');
    }
}
