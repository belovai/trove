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

    public function test_the_safety_query_parameter_overrides_the_viewer_default(): void
    {
        Media::factory()->create();
        $sketchy = Media::factory()->sketchy()->create();

        $this->get('/posts?safety=sketchy')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('media.data', 1)
                ->where('media.data.0.hash_id', $sketchy->hash_id));
    }

    public function test_an_empty_safety_selection_lists_nothing(): void
    {
        Media::factory()->create();

        $this->get('/posts?safety=')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('media.data', 0));
    }

    public function test_an_unknown_safety_value_is_rejected(): void
    {
        $this->get('/posts?safety=spicy')->assertSessionHasErrors('safety.0');
    }

    public function test_the_untagged_filter_lists_only_items_without_tags(): void
    {
        Media::factory()->tagged()->create();
        $untagged = Media::factory()->create();

        $this->get('/posts?untagged=1')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('media.data', 1)
                ->where('media.data.0.hash_id', $untagged->hash_id));
    }

    public function test_the_effective_filter_state_is_sent_to_the_page(): void
    {
        $this->get('/posts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.safety', ['safe'])
                ->where('filters.untagged', false)
                ->where('filters.unlisted', false));

        $this->get('/posts?safety=safe,unsafe&untagged=1&unlisted=1')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.safety', ['safe', 'unsafe'])
                ->where('filters.untagged', true)
                ->where('filters.unlisted', true));
    }

    public function test_the_unlisted_filter_lists_only_the_viewers_own_unlisted_items(): void
    {
        $viewer = User::factory()->create();
        $ownUnlisted = Media::factory()->for($viewer, 'uploader')->unlisted()->create();
        Media::factory()->unlisted()->create();
        Media::factory()->for($viewer, 'uploader')->create();

        $this->actingAs($viewer)
            ->get('/posts?unlisted=1')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('media.data', 1)
                ->where('media.data.0.hash_id', $ownUnlisted->hash_id));
    }

    public function test_a_guest_sees_nothing_with_the_unlisted_filter(): void
    {
        Media::factory()->unlisted()->create();

        $this->get('/posts?unlisted=1')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('media.data', 0));
    }

    public function test_the_viewer_default_widens_the_listing_without_a_parameter(): void
    {
        $viewer = User::factory()->create(['default_safety_filter' => 'unsafe']);
        Media::factory()->unsafe()->create();

        $this->actingAs($viewer)
            ->get('/posts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('media.data', 1)
                ->where('filters.safety', ['safe', 'sketchy', 'unsafe']));
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

    public function test_the_default_sort_is_newest(): void
    {
        $older = Media::factory()->create(['created_at' => now()->subDay()]);
        $newer = Media::factory()->create(['created_at' => now()]);

        $this->get('/posts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.sort', 'newest')
                ->where('media.data.0.hash_id', $newer->hash_id)
                ->where('media.data.1.hash_id', $older->hash_id));
    }

    public function test_sorting_by_score_puts_the_highest_first(): void
    {
        $low = Media::factory()->create();
        $high = Media::factory()->create();
        $negative = Media::factory()->create();

        $low->forceFill(['score' => 1])->save();
        $high->forceFill(['score' => 5])->save();
        $negative->forceFill(['score' => -3])->save();

        $this->get('/posts?sort=score')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.sort', 'score')
                ->where('media.data.0.hash_id', $high->hash_id)
                ->where('media.data.1.hash_id', $low->hash_id)
                ->where('media.data.2.hash_id', $negative->hash_id));
    }

    public function test_equal_scores_break_the_tie_by_recency(): void
    {
        $older = Media::factory()->create(['created_at' => now()->subDay()]);
        $newer = Media::factory()->create(['created_at' => now()]);

        $older->forceFill(['score' => 2])->save();
        $newer->forceFill(['score' => 2])->save();

        $this->get('/posts?sort=score')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('media.data.0.hash_id', $newer->hash_id));
    }

    public function test_sorting_oldest_first(): void
    {
        $older = Media::factory()->create(['created_at' => now()->subDay()]);
        Media::factory()->create(['created_at' => now()]);

        $this->get('/posts?sort=oldest')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('media.data.0.hash_id', $older->hash_id));
    }

    public function test_an_unknown_sort_is_rejected(): void
    {
        $this->get('/posts?sort=chaos')->assertSessionHasErrors('sort');
    }
}
