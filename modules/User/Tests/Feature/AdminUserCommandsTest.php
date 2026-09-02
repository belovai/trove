<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * The console escape hatch: these commands answer to nobody, so the cases that
 * matter are the ones the web policy refuses — an administrator on an
 * administrator.
 */
final class AdminUserCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_bans_and_unbans_an_administrator(): void
    {
        $admin = User::factory()->create(['username' => 'root', 'rank' => UserRank::Administrator]);

        $this->artisan('user:ban', ['username' => 'root', '--reason' => 'stolen'])->assertSuccessful();

        $admin->refresh();
        $this->assertTrue($admin->isBanned());
        $this->assertSame('stolen', $admin->ban_reason);

        $this->artisan('user:unban', ['username' => 'root'])->assertSuccessful();

        $admin->refresh();
        $this->assertFalse($admin->isBanned());
        $this->assertNull($admin->ban_reason);
    }

    public function test_it_changes_a_rank(): void
    {
        User::factory()->create(['username' => 'root', 'rank' => UserRank::Administrator]);

        $this->artisan('user:rank', ['username' => 'root', 'rank' => 'regular'])->assertSuccessful();

        $this->assertSame(UserRank::Regular, User::query()->where('username', 'root')->sole()->rank);
    }

    public function test_it_rejects_an_unknown_rank(): void
    {
        User::factory()->create(['username' => 'root']);

        $this->artisan('user:rank', ['username' => 'root', 'rank' => 'emperor'])->assertFailed();
    }

    public function test_it_sets_a_given_password(): void
    {
        User::factory()->create(['username' => 'root']);

        $this->artisan('user:password', ['username' => 'root', '--password' => 'correct-horse-battery'])
            ->assertSuccessful();

        $this->assertTrue(Hash::check('correct-horse-battery', User::query()->where('username', 'root')->sole()->password));
    }

    public function test_it_generates_and_prints_a_password(): void
    {
        User::factory()->create(['username' => 'root']);

        $this->assertSame(0, Artisan::call('user:password', ['username' => 'root']));

        preg_match('/Generated password\s+\.*\s*(\S+)\s/', Artisan::output(), $matches);

        $this->assertArrayHasKey(1, $matches);
        $this->assertTrue(Hash::check($matches[1], User::query()->where('username', 'root')->sole()->password));
    }

    public function test_it_fails_on_an_unknown_username(): void
    {
        $this->artisan('user:ban', ['username' => 'nobody'])->assertFailed();
        $this->artisan('user:unban', ['username' => 'nobody'])->assertFailed();
        $this->artisan('user:rank', ['username' => 'nobody', 'rank' => 'regular'])->assertFailed();
        $this->artisan('user:password', ['username' => 'nobody'])->assertFailed();
    }
}
