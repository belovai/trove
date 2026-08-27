<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Media;
use Modules\User\Models\User;
use Tests\TestCase;

final class MediaPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_may_edit_and_delete(): void
    {
        $owner = User::factory()->create();
        $media = Media::factory()->for($owner, 'uploader')->create();

        $this->assertTrue($owner->can('update', $media));
        $this->assertTrue($owner->can('delete', $media));
    }

    public function test_a_stranger_may_not(): void
    {
        $media = Media::factory()->create();
        $stranger = User::factory()->create();

        $this->assertFalse($stranger->can('update', $media));
        $this->assertFalse($stranger->can('delete', $media));
    }

    public function test_a_moderator_may(): void
    {
        $media = Media::factory()->create();
        $moderator = User::factory()->moderator()->create();

        $this->assertTrue($moderator->can('update', $media));
        $this->assertTrue($moderator->can('delete', $media));
    }

    public function test_viewing_follows_the_same_rules_as_the_scope(): void
    {
        $owner = User::factory()->create();
        $private = Media::factory()->private()->for($owner, 'uploader')->create();

        $this->assertTrue($owner->can('view', $private));
        $this->assertFalse(User::factory()->create()->can('view', $private));
    }
}
