<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Media\Models\Media;
use Modules\User\Models\User;
use Tests\TestCase;

final class BrowseMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_sees_public_items(): void
    {
        $media = Media::factory()->create();

        $this->get('/posts')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('media/Index')
                ->has('media.data', 1)
                ->where('media.data.0.hash_id', $media->hash_id));
    }

    public function test_unlisted_items_are_absent_from_browse(): void
    {
        Media::factory()->unlisted()->create();

        $this->get('/posts')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('media.data', 0));
    }

    public function test_the_safety_filter_applies_to_the_listing(): void
    {
        Media::factory()->unsafe()->create();

        $this->get('/posts')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('media.data', 0));
    }

    public function test_the_internal_id_and_storage_path_are_never_sent(): void
    {
        Media::factory()->create();

        $this->get('/posts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->missing('media.data.0.id')
                ->missing('media.data.0.storage_path'));
    }

    public function test_an_anonymous_item_does_not_name_its_uploader(): void
    {
        $uploader = User::factory()->create(['username' => 'arpad']);
        Media::factory()->for($uploader, 'uploader')->create(['is_anonymous' => true]);

        $response = $this->get('/posts');

        $response->assertDontSee('arpad');
    }
}
