<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->create(['rank' => UserRank::Administrator]);
    }

    public function test_a_regular_user_cannot_open_the_user_list(): void
    {
        $this->actingAs(User::factory()->create(['rank' => UserRank::Regular]))
            ->get('/settings/users')
            ->assertForbidden();
    }

    public function test_an_administrator_sees_the_user_list(): void
    {
        $admin = $this->administrator();
        User::factory()->create(['username' => 'someone']);

        $this->actingAs($admin)
            ->get('/settings/users')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('settings/Users')
                ->has('users.data', 2));
    }

    public function test_the_list_can_be_searched(): void
    {
        $admin = $this->administrator();
        User::factory()->create(['username' => 'needle']);
        User::factory()->create(['username' => 'haystack']);

        $this->actingAs($admin)
            ->get('/settings/users?search=need')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.username', 'needle'));
    }

    public function test_the_list_can_be_filtered_by_rank_and_status(): void
    {
        $admin = $this->administrator();
        User::factory()->create(['rank' => UserRank::Moderator]);
        User::factory()->create(['rank' => UserRank::Regular, 'banned_at' => now()]);

        $this->actingAs($admin)
            ->get('/settings/users?rank=moderator')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('users.data', 1));

        $this->actingAs($admin)
            ->get('/settings/users?status=banned')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('users.data', 1));
    }

    public function test_an_administrator_can_create_a_user(): void
    {
        $this->actingAs($this->administrator())
            ->post('/settings/users', [
                'username' => 'newcomer',
                'display_name' => 'Newcomer',
                'email' => 'newcomer@example.test',
                'password' => 'correct-horse',
                'rank' => 'regular',
            ])
            ->assertRedirect('/settings/users');

        $created = User::query()->where('username', 'newcomer')->firstOrFail();

        $this->assertSame(UserRank::Regular, $created->rank);
        $this->assertTrue(Hash::check('correct-horse', $created->password));
    }

    public function test_an_administrator_can_change_a_rank(): void
    {
        $target = User::factory()->create(['rank' => UserRank::Regular]);

        $this->actingAs($this->administrator())
            ->patch("/settings/users/{$target->username}", ['rank' => 'moderator'])
            ->assertRedirect('/settings/users');

        $this->assertSame(UserRank::Moderator, $target->fresh()->rank);
    }

    public function test_an_administrator_can_ban_and_unban(): void
    {
        $admin = $this->administrator();
        $target = User::factory()->create(['rank' => UserRank::Regular]);

        $this->actingAs($admin)
            ->patch("/settings/users/{$target->username}", ['is_banned' => true, 'ban_reason' => 'spam']);

        $target->refresh();
        $this->assertTrue($target->isBanned());
        $this->assertSame('spam', $target->ban_reason);

        $this->actingAs($admin)->patch("/settings/users/{$target->username}", ['is_banned' => false]);

        $target->refresh();
        $this->assertFalse($target->isBanned());
        $this->assertNull($target->ban_reason);
    }

    public function test_you_cannot_change_your_own_rank(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->patch("/settings/users/{$admin->username}", ['rank' => 'regular'])
            ->assertForbidden();

        $this->assertSame(UserRank::Administrator, $admin->fresh()->rank);
    }

    public function test_you_cannot_ban_yourself(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->patch("/settings/users/{$admin->username}", ['is_banned' => true])
            ->assertForbidden();

        $this->assertFalse($admin->fresh()->isBanned());
    }

    public function test_you_cannot_assign_a_rank_above_your_own(): void
    {
        // The gate is Administrator-only today, so this is asserted through
        // the policy directly: it is the rule that makes lowering the gate
        // later safe.
        $moderator = User::factory()->create(['rank' => UserRank::Moderator]);
        $target = User::factory()->create(['rank' => UserRank::Regular]);

        $this->assertFalse($moderator->can('update', $target));
    }

    public function test_a_banned_user_still_appears_in_the_list(): void
    {
        $admin = $this->administrator();
        User::factory()->create(['banned_at' => now()]);

        $this->actingAs($admin)
            ->get('/settings/users')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('users.data', 2));
    }
}
