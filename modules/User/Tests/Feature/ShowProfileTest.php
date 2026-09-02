<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class ShowProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_sees_a_profile_and_its_public_uploads(): void
    {
        $user = User::factory()->create(['username' => 'ada', 'display_name' => 'Ada']);
        $item = Media::factory()->create([
            'user_id' => $user->id,
            'visibility' => Visibility::Public,
            'safety_rating' => SafetyRating::Safe,
        ]);

        $this->get('/u/ada')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('users/Profile')
                ->where('profile.username', 'ada')
                ->where('profile.display_name', 'Ada')
                ->where('profile.rank', $user->rank->value)
                ->where('profile.upload_count', 1)
                ->where('profile.is_banned', false)
                ->where('uploads.data.0.hash_id', $item->hash_id)
                ->where('notices.uploads_hidden', false)
            );
    }

    public function test_it_404s_for_an_unknown_username(): void
    {
        $this->get('/u/nobody')->assertNotFound();
    }

    public function test_it_404s_for_a_soft_deleted_account(): void
    {
        $user = User::factory()->create(['username' => 'gone']);
        $user->delete();

        $this->get('/u/gone')->assertNotFound();
    }

    public function test_it_excludes_another_users_private_item(): void
    {
        $user = User::factory()->create(['username' => 'ada']);
        Media::factory()->create(['user_id' => $user->id, 'visibility' => Visibility::Private]);

        $this->get('/u/ada')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('profile.upload_count', 0)
                ->count('uploads.data', 0)
            );
    }

    public function test_it_excludes_unlisted_items_even_from_the_owner(): void
    {
        $user = User::factory()->create(['username' => 'ada']);
        Media::factory()->create(['user_id' => $user->id, 'visibility' => Visibility::Unlisted]);

        $this->actingAs($user)->get('/u/ada')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->count('uploads.data', 0));
    }

    public function test_it_applies_the_viewers_safety_filter(): void
    {
        $user = User::factory()->create(['username' => 'ada']);
        Media::factory()->create(['user_id' => $user->id, 'safety_rating' => SafetyRating::Unsafe]);

        $viewer = User::factory()->create([
            'rank' => UserRank::Regular,
            'default_safety_filter' => SafetyRating::Safe,
        ]);

        $this->actingAs($viewer)->get('/u/ada')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->count('uploads.data', 0));
    }

    public function test_the_switch_hides_the_list_from_a_stranger(): void
    {
        $user = User::factory()->create(['username' => 'ada', 'show_uploads' => false]);
        Media::factory()->create(['user_id' => $user->id]);

        $this->get('/u/ada')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('uploads', null)
                ->where('notices.uploads_hidden', false)
            );
    }

    public function test_the_owner_keeps_their_list_when_the_switch_is_off(): void
    {
        $user = User::factory()->create(['username' => 'ada', 'show_uploads' => false]);
        Media::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get('/u/ada')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->count('uploads.data', 1));
    }

    public function test_a_moderator_sees_a_hidden_list_and_is_told_it_is_hidden(): void
    {
        $user = User::factory()->create(['username' => 'ada', 'show_uploads' => false]);
        Media::factory()->create(['user_id' => $user->id]);
        $moderator = User::factory()->create(['rank' => UserRank::Moderator]);

        $this->actingAs($moderator)->get('/u/ada')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->count('uploads.data', 1)
                ->where('notices.uploads_hidden', true)
            );
    }

    public function test_an_anonymous_item_is_hidden_from_a_stranger(): void
    {
        $user = User::factory()->create(['username' => 'ada']);
        Media::factory()->create(['user_id' => $user->id, 'is_anonymous' => true]);

        $this->get('/u/ada')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->count('uploads.data', 0)
                ->where('profile.upload_count', 0)
                ->where('notices.has_anonymous', false)
            );
    }

    public function test_an_anonymous_item_is_shown_to_the_owner_and_flagged(): void
    {
        $user = User::factory()->create(['username' => 'ada']);
        Media::factory()->create(['user_id' => $user->id, 'is_anonymous' => true]);

        $this->actingAs($user)->get('/u/ada')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->count('uploads.data', 1)
                ->where('uploads.data.0.is_anonymous', true)
            );
    }

    public function test_a_moderator_sees_anonymous_items_flagged_and_noticed(): void
    {
        $user = User::factory()->create(['username' => 'ada']);
        Media::factory()->create(['user_id' => $user->id, 'is_anonymous' => true]);
        $moderator = User::factory()->create(['rank' => UserRank::Moderator]);

        $this->actingAs($moderator)->get('/u/ada')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('uploads.data.0.is_anonymous', true)
                ->where('notices.has_anonymous', true)
            );
    }

    public function test_the_anonymous_flag_never_reaches_a_stranger(): void
    {
        $user = User::factory()->create(['username' => 'ada']);
        Media::factory()->create(['user_id' => $user->id, 'is_anonymous' => false]);

        $this->get('/u/ada')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->missing('uploads.data.0.is_anonymous'));
    }

    public function test_a_banned_profile_404s_for_a_stranger(): void
    {
        User::factory()->create(['username' => 'ada', 'banned_at' => now(), 'ban_reason' => 'spam']);

        $this->get('/u/ada')->assertNotFound();
    }

    public function test_a_banned_profile_renders_for_a_moderator(): void
    {
        User::factory()->create(['username' => 'ada', 'banned_at' => now(), 'ban_reason' => 'spam']);
        $moderator = User::factory()->create(['rank' => UserRank::Moderator]);

        $this->actingAs($moderator)->get('/u/ada')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('profile.is_banned', true));
    }
}
