<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Media\Models\Media;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class ShowMediaPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exposes_the_uploader_as_a_linkable_pair(): void
    {
        $uploader = User::factory()->create(['username' => 'ada', 'display_name' => 'Ada']);
        $item = Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => false]);

        $this->get('/m/'.$item->hash_id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('media.uploader.username', 'ada')
                ->where('media.uploader.display_name', 'Ada')
                ->where('media.uploader.linkable', true)
            );
    }

    public function test_an_anonymous_item_exposes_no_uploader_to_a_stranger(): void
    {
        $uploader = User::factory()->create();
        $item = Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => true]);

        $this->get('/m/'.$item->hash_id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('media.uploader', null));
    }

    public function test_an_anonymous_item_exposes_its_uploader_pair_to_the_uploader_themselves(): void
    {
        $uploader = User::factory()->create(['username' => 'ada', 'display_name' => 'Ada']);
        $item = Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => true]);

        $this->actingAs($uploader)
            ->get('/m/'.$item->hash_id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('media.uploader.username', 'ada')
                ->where('media.uploader.display_name', 'Ada')
            );
    }

    public function test_an_anonymous_item_exposes_its_uploader_pair_to_a_moderator(): void
    {
        $uploader = User::factory()->create(['username' => 'ada', 'display_name' => 'Ada']);
        $item = Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => true]);
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($moderator)
            ->get('/m/'.$item->hash_id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('media.uploader.username', 'ada')
                ->where('media.uploader.display_name', 'Ada')
            );
    }

    public function test_a_banned_uploader_is_not_linkable_for_a_guest(): void
    {
        $uploader = User::factory()->banned()->create();
        $item = Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => false]);

        $this->get('/m/'.$item->hash_id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('media.uploader.linkable', false));
    }

    public function test_a_banned_uploader_is_linkable_for_a_moderator(): void
    {
        $uploader = User::factory()->banned()->create();
        $item = Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => false]);
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($moderator)
            ->get('/m/'.$item->hash_id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('media.uploader.linkable', true));
    }

    public function test_a_soft_deleted_uploader_is_not_linkable_for_anyone(): void
    {
        $uploader = User::factory()->create();
        $item = Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => false]);
        $uploader->delete();
        $moderator = User::factory()->moderator()->create();

        $this->get('/m/'.$item->hash_id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('media.uploader.linkable', false));

        $this->actingAs($moderator)
            ->get('/m/'.$item->hash_id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('media.uploader.linkable', false));
    }

    public function test_the_score_and_the_viewers_own_vote_are_sent(): void
    {
        $media = Media::factory()->create();
        $voter = User::factory()->create(['rank' => UserRank::Regular]);

        $this->actingAs($voter)->post("/m/{$media->hash_id}/vote", ['value' => -1]);

        $this->actingAs($voter)->get("/m/{$media->hash_id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('media.score', -1)
                ->where('media.viewer_vote', -1)
                ->where('can.vote', true)
                ->where('vote_blocked_reason', null));
    }

    public function test_a_guest_gets_the_score_but_no_vote(): void
    {
        $media = Media::factory()->create();

        $this->get("/m/{$media->hash_id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('media.score', 0)
                ->where('media.viewer_vote', null)
                ->where('can.vote', false)
                ->where('vote_blocked_reason', 'guest'));
    }

    public function test_the_uploader_is_told_why_they_cannot_vote(): void
    {
        $uploader = User::factory()->create(['rank' => UserRank::Regular]);
        $media = Media::factory()->for($uploader, 'uploader')->create();

        $this->actingAs($uploader)->get("/m/{$media->hash_id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('can.vote', false)
                ->where('vote_blocked_reason', 'own'));
    }

    public function test_a_restricted_viewer_is_told_why_they_cannot_vote(): void
    {
        $media = Media::factory()->create();

        $this->actingAs(User::factory()->create(['rank' => UserRank::Restricted]))
            ->get("/m/{$media->hash_id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('can.vote', false)
                ->where('vote_blocked_reason', 'restricted'));
    }

    public function test_the_viewers_own_favorite_state_is_sent(): void
    {
        $media = Media::factory()->create();
        $viewer = User::factory()->create(['rank' => UserRank::Regular]);

        $this->actingAs($viewer)->post("/m/{$media->hash_id}/favorite");

        $this->actingAs($viewer)->get("/m/{$media->hash_id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('media.is_favorited', true)
                ->where('can.favorite', true));
    }

    public function test_a_guest_gets_is_favorited_false_and_cannot_favorite(): void
    {
        $media = Media::factory()->create();

        $this->get("/m/{$media->hash_id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('media.is_favorited', false)
                ->where('can.favorite', false));
    }

    public function test_a_restricted_viewer_cannot_favorite(): void
    {
        $media = Media::factory()->create();

        $this->actingAs(User::factory()->create(['rank' => UserRank::Restricted]))
            ->get("/m/{$media->hash_id}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('can.favorite', false));
    }

    public function test_the_uploader_can_favorite_their_own_item(): void
    {
        $uploader = User::factory()->create(['rank' => UserRank::Regular]);
        $media = Media::factory()->for($uploader, 'uploader')->create();

        $this->actingAs($uploader)->get("/m/{$media->hash_id}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('can.favorite', true));
    }
}
