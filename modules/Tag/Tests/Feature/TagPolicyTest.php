<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;
use Modules\User\Models\User;
use Tests\TestCase;

final class TagPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_regular_user_may_not_change_the_taxonomy(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $this->assertFalse($user->can('update', $tag));
        $this->assertFalse($user->can('delete', $tag));
        $this->assertFalse($user->can('merge', $tag));
    }

    public function test_a_moderator_may_change_tags_but_not_categories(): void
    {
        $moderator = User::factory()->moderator()->create();
        $tag = Tag::factory()->create();

        $this->assertTrue($moderator->can('update', $tag));
        $this->assertTrue($moderator->can('merge', $tag));
        $this->assertFalse($moderator->can('update', TagCategory::default()));
    }

    public function test_an_administrator_may_change_categories(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->assertTrue($administrator->can('update', TagCategory::default()));
        $this->assertTrue($administrator->can('create', TagCategory::class));
    }

    public function test_the_default_category_may_not_be_deleted(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->assertFalse($administrator->can('delete', TagCategory::default()));
    }
}
