<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Media\Models\Media;
use Modules\Tag\Actions\CreateAlias;
use Modules\Tag\Actions\CreateImplication;
use Modules\Tag\Actions\SyncMediaTags;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;
use Modules\User\Models\User;
use Tests\TestCase;

final class BrowseTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_index_lists_tags_by_usage(): void
    {
        Tag::factory()->create(['name' => 'rare', 'usage_count' => 1]);
        Tag::factory()->create(['name' => 'common', 'usage_count' => 90]);

        $this->get('/tags')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('tags/Index')
                ->where('tags.data.0.name', 'common'),
        );
    }

    public function test_the_index_filters_by_name(): void
    {
        Tag::factory()->create(['name' => 'cat']);
        Tag::factory()->create(['name' => 'dog']);

        $this->get('/tags?q=ca')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page->count('tags.data', 1),
        );
    }

    public function test_the_index_filters_by_category(): void
    {
        $animals = TagCategory::factory()->create(['name' => 'animals']);
        $objects = TagCategory::factory()->create(['name' => 'objects']);
        Tag::factory()->create(['name' => 'cat', 'category_id' => $animals->id]);
        Tag::factory()->create(['name' => 'chair', 'category_id' => $objects->id]);

        $this->get('/tags?category=animals')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->count('tags.data', 1)
                ->where('tags.data.0.name', 'cat')
                ->where('filters.category', 'animals'),
        );
    }

    public function test_the_index_carries_categories_with_tag_counts(): void
    {
        $animals = TagCategory::factory()->create(['name' => 'animals']);
        Tag::factory()->count(2)->create(['category_id' => $animals->id]);
        Tag::factory()->create(['category_id' => null]);

        $this->get('/tags')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('categories', 1)
                ->where('categories.0.name', 'animals')
                ->where('categories.0.tags_count', 2),
        );
    }

    public function test_autocomplete_matches_tags_and_aliases(): void
    {
        $cat = Tag::factory()->create(['name' => 'cat', 'usage_count' => 5]);
        app(CreateAlias::class)->handle($cat, 'kitty');

        $this->getJson('/tags/autocomplete?q=kit')
            ->assertOk()
            ->assertJsonPath('0.name', 'cat')
            ->assertJsonPath('0.matched', 'kitty');
    }

    public function test_the_tag_page_carries_its_tree_related_tags_and_media(): void
    {
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        $animal = Tag::factory()->create(['name' => 'animal']);
        app(CreateImplication::class)->handle($calico, $cat);
        app(CreateImplication::class)->handle($cat, $animal);
        app(SyncMediaTags::class)->handle(Media::factory()->create(), [$calico->id], null);

        $this->get('/tags/cat')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('tags/Show')
                ->where('tag.name', 'cat')
                ->where('tag.ancestors', ['calico'])
                ->where('tag.descendants', ['animal'])
                ->count('media', 1),
        );
    }

    public function test_the_tag_page_hides_media_the_viewer_may_not_see(): void
    {
        $cat = Tag::factory()->create(['name' => 'cat']);
        $private = Media::factory()->private()->create();
        app(SyncMediaTags::class)->handle($private, [$cat->id], null);

        $this->get('/tags/cat')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page->count('media', 0),
        );
    }

    public function test_the_tag_page_honours_the_safety_filter_parameter(): void
    {
        $cat = Tag::factory()->create(['name' => 'cat']);
        $sketchy = Media::factory()->sketchy()->create();
        app(SyncMediaTags::class)->handle($sketchy, [$cat->id], null);

        $this->get('/tags/cat')->assertInertia(
            fn (AssertableInertia $page) => $page->count('media', 0)->where('filters.safety', ['safe']),
        );

        $this->get('/tags/cat?safety=sketchy')->assertInertia(
            fn (AssertableInertia $page) => $page->count('media', 1)->where('filters.safety', ['sketchy']),
        );
    }

    public function test_the_edit_permission_flag_follows_the_gate(): void
    {
        Tag::factory()->create(['name' => 'cat']);

        $this->actingAs(User::factory()->create())->get('/tags/cat')->assertInertia(
            fn (AssertableInertia $page) => $page->where('can.manage', false),
        );

        $this->actingAs(User::factory()->moderator()->create())->get('/tags/cat')->assertInertia(
            fn (AssertableInertia $page) => $page->where('can.manage', true),
        );
    }
}
