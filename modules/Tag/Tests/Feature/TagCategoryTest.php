<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;
use Modules\User\Models\User;
use Tests\TestCase;

final class TagCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_moderator_may_not_reach_the_admin_page(): void
    {
        $this->actingAs(User::factory()->moderator()->create())
            ->get('/settings/tags')
            ->assertForbidden();
    }

    public function test_the_old_admin_url_redirects(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->get('/admin/tags')
            ->assertRedirect('/settings/tags');
    }

    public function test_the_admin_page_carries_categories_and_the_health_report(): void
    {
        Tag::factory()->create(['name' => 'orphan']);

        $this->actingAs(User::factory()->administrator()->create())
            ->get('/settings/tags')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('settings/Tags')
                    ->count('categories', 1)
                    ->where('health.unused.0.name', 'orphan'),
            );
    }

    public function test_a_category_can_be_created(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->post('/admin/tags/categories', ['name' => 'artist', 'color' => '#aa0000', 'sort_order' => 1])
            ->assertRedirect();

        $this->assertTrue(TagCategory::query()->where('name', 'artist')->exists());
    }

    public function test_deleting_a_category_reassigns_its_tags_to_the_default(): void
    {
        $artist = TagCategory::factory()->create(['name' => 'artist']);
        $tag = Tag::factory()->for($artist, 'category')->create(['name' => 'someone']);

        $this->actingAs(User::factory()->administrator()->create())
            ->delete("/admin/tags/categories/{$artist->id}")
            ->assertRedirect();

        $this->assertSame(TagCategory::default()->id, $tag->fresh()->category_id);
    }

    public function test_the_default_category_may_not_be_deleted(): void
    {
        $default = TagCategory::default();

        $this->actingAs(User::factory()->administrator()->create())
            ->delete("/admin/tags/categories/{$default->id}")
            ->assertForbidden();

        $this->assertTrue(TagCategory::query()->whereKey($default->id)->exists());
    }
}
