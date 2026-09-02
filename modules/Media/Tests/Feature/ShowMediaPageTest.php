<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Media\Models\Media;
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
}
