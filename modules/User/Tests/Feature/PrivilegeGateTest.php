<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\User\Models\User;
use Tests\TestCase;

final class PrivilegeGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_a_gate_for_each_privilege_in_the_module_map(): void
    {
        $this->assertTrue(Gate::has('user.manage'));
    }

    public function test_a_rank_below_the_minimum_is_denied(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(Gate::forUser($user)->allows('user.manage'));
    }

    public function test_a_rank_at_the_minimum_is_allowed(): void
    {
        $user = User::factory()->administrator()->create();

        $this->assertTrue(Gate::forUser($user)->allows('user.manage'));
    }

    public function test_a_banned_user_is_denied_everything(): void
    {
        $user = User::factory()->administrator()->banned()->create();

        $this->assertFalse(Gate::forUser($user)->allows('user.manage'));
    }

    public function test_the_display_name_falls_back_to_the_username(): void
    {
        $user = User::factory()->create([
            'username' => 'rp',
            'display_name' => null,
        ]);

        $this->assertSame('rp', $user->displayName());
    }
}
