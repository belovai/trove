<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Media;
use Modules\User\Models\User;
use Tests\TestCase;

final class MediaVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_sees_only_public_items(): void
    {
        $public = Media::factory()->create();
        Media::factory()->authenticatedOnly()->create();
        Media::factory()->private()->create();

        $visible = Media::query()->visibleTo(null)->pluck('id');

        $this->assertSame([$public->id], $visible->all());
    }

    public function test_a_logged_in_user_also_sees_authenticated_items(): void
    {
        $public = Media::factory()->create();
        $members = Media::factory()->authenticatedOnly()->create();

        $visible = Media::query()->visibleTo(User::factory()->create())->pluck('id');

        $this->assertEqualsCanonicalizing([$public->id, $members->id], $visible->all());
    }

    public function test_a_restricted_user_does_not_see_authenticated_items(): void
    {
        $public = Media::factory()->create();
        Media::factory()->authenticatedOnly()->create();

        $visible = Media::query()->visibleTo(User::factory()->restricted()->create())->pluck('id');

        $this->assertSame([$public->id], $visible->all());
    }

    public function test_a_restricted_user_still_sees_their_own_authenticated_item(): void
    {
        $owner = User::factory()->restricted()->create();
        $own = Media::factory()->authenticatedOnly()->for($owner, 'uploader')->create();

        $this->assertTrue(Media::query()->visibleTo($owner)->whereKey($own->id)->exists());
    }

    public function test_a_private_item_is_visible_only_to_its_uploader(): void
    {
        $owner = User::factory()->create();
        $item = Media::factory()->private()->for($owner, 'uploader')->create();

        $this->assertTrue(Media::query()->visibleTo($owner)->whereKey($item->id)->exists());
        $this->assertFalse(Media::query()->visibleTo(User::factory()->create())->whereKey($item->id)->exists());
        $this->assertFalse(Media::query()->visibleTo(null)->whereKey($item->id)->exists());
    }

    public function test_a_moderator_sees_private_items(): void
    {
        $item = Media::factory()->private()->create();

        $this->assertTrue(Media::query()->visibleTo(User::factory()->moderator()->create())->whereKey($item->id)->exists());
    }

    public function test_a_banned_moderator_does_not(): void
    {
        $item = Media::factory()->private()->create();
        $banned = User::factory()->moderator()->create(['banned_at' => now()]);

        $this->assertFalse(Media::query()->visibleTo($banned)->whereKey($item->id)->exists());
    }

    public function test_unlisted_items_are_reachable_but_not_listed(): void
    {
        $item = Media::factory()->unlisted()->create();

        $this->assertTrue(Media::query()->visibleTo(null)->whereKey($item->id)->exists());
        $this->assertFalse(Media::query()->visibleTo(null)->listable()->whereKey($item->id)->exists());
    }

    public function test_the_safety_filter_hides_but_does_not_forbid(): void
    {
        $unsafe = Media::factory()->unsafe()->create();
        $viewer = User::factory()->create(); // default filter: safe

        $this->assertFalse(Media::query()->visibleTo($viewer)->withinSafetyFilter($viewer)->whereKey($unsafe->id)->exists());
        $this->assertTrue(Media::query()->visibleTo($viewer)->whereKey($unsafe->id)->exists());
    }
}
