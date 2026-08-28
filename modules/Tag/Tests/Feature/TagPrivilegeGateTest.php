<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use Tests\TestCase;

final class TagPrivilegeGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_regular_user_may_tag_but_not_manage_the_taxonomy(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('tag.edit'));
        $this->assertFalse($user->can('tag.manage'));
        $this->assertFalse($user->can('tag.admin'));
    }

    public function test_a_moderator_may_manage_the_taxonomy_but_not_administer_it(): void
    {
        $moderator = User::factory()->moderator()->create();

        $this->assertTrue($moderator->can('tag.manage'));
        $this->assertFalse($moderator->can('tag.admin'));
    }

    public function test_an_administrator_may_do_everything(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->assertTrue($administrator->can('tag.admin'));
    }
}
