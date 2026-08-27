<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\User\Models\User;
use Tests\TestCase;

final class MediaPrivilegeGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_restricted_user_cannot_upload(): void
    {
        $this->assertFalse(Gate::forUser(User::factory()->restricted()->create())->allows('media.upload'));
    }

    public function test_a_regular_user_can_upload(): void
    {
        $this->assertTrue(Gate::forUser(User::factory()->create())->allows('media.upload'));
    }

    public function test_a_regular_user_cannot_moderate(): void
    {
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows('media.moderate'));
    }

    public function test_a_moderator_can_moderate(): void
    {
        $this->assertTrue(Gate::forUser(User::factory()->moderator()->create())->allows('media.moderate'));
    }

    public function test_a_banned_moderator_can_do_nothing(): void
    {
        $banned = User::factory()->moderator()->create(['banned_at' => now()]);

        $this->assertFalse(Gate::forUser($banned)->allows('media.upload'));
        $this->assertFalse(Gate::forUser($banned)->allows('media.moderate'));
    }
}
