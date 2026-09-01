<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_user_with_the_given_rank_and_password(): void
    {
        $this->artisan('user:create', [
            'username' => 'zara',
            '--no-interaction' => true,
            '--rank' => 'administrator',
            '--password' => 'correct-horse-battery',
            '--display-name' => 'Zara',
            '--email' => 'zara@example.test',
        ])->assertSuccessful();

        $user = User::query()->where('username', 'zara')->sole();

        $this->assertSame(UserRank::Administrator, $user->rank);
        $this->assertSame('Zara', $user->display_name);
        $this->assertSame('zara@example.test', $user->email);
        $this->assertTrue(Hash::check('correct-horse-battery', $user->password));
    }

    public function test_it_defaults_to_the_regular_rank(): void
    {
        $this->artisan('user:create', ['username' => 'nobu', '--password' => 'correct-horse-battery', '--no-interaction' => true])
            ->assertSuccessful();

        $this->assertSame(UserRank::Regular, User::query()->where('username', 'nobu')->sole()->rank);
    }

    public function test_it_generates_a_sixteen_character_password_when_none_is_given(): void
    {
        $this->assertSame(0, Artisan::call('user:create', ['username' => 'kai', '--no-interaction' => true]));

        preg_match('/Generated password\\s+\\.*\\s*(\\S+)\\s/', Artisan::output(), $matches);

        $password = $matches[1] ?? '';

        $this->assertSame(16, mb_strlen($password));
        $this->assertTrue(Hash::check($password, User::query()->where('username', 'kai')->sole()->password));
    }

    public function test_it_prints_the_generated_password_once(): void
    {
        $this->artisan('user:create', ['username' => 'mira', '--no-interaction' => true])
            ->expectsOutputToContain('Generated password')
            ->assertSuccessful();
    }

    public function test_it_rejects_a_duplicate_username(): void
    {
        User::factory()->create(['username' => 'taken']);

        $this->artisan('user:create', ['username' => 'taken', '--no-interaction' => true])->assertFailed();

        $this->assertSame(1, User::query()->where('username', 'taken')->count());
    }

    public function test_it_rejects_an_unknown_rank(): void
    {
        $this->artisan('user:create', ['username' => 'ari', '--rank' => 'overlord', '--no-interaction' => true])->assertFailed();

        $this->assertSame(0, User::query()->where('username', 'ari')->count());
    }

    public function test_it_rejects_a_short_password(): void
    {
        $this->artisan('user:create', ['username' => 'ari', '--password' => 'short', '--no-interaction' => true])->assertFailed();

        $this->assertSame(0, User::query()->where('username', 'ari')->count());
    }

    public function test_it_walks_through_every_field_when_nothing_is_passed(): void
    {
        $this->artisan('user:create')
            ->expectsQuestion('Username', 'wizard')
            ->expectsQuestion('Display name', 'Wizard')
            ->expectsQuestion('Email address', 'wizard@example.test')
            ->expectsQuestion('Rank', 'moderator')
            ->expectsQuestion('Password', 'correct-horse-battery')
            ->expectsQuestion('Confirm password', 'correct-horse-battery')
            ->expectsConfirmation('Create wizard as moderator?', 'yes')
            ->assertSuccessful();

        $user = User::query()->where('username', 'wizard')->sole();

        $this->assertSame(UserRank::Moderator, $user->rank);
        $this->assertSame('wizard@example.test', $user->email);
        $this->assertTrue(Hash::check('correct-horse-battery', $user->password));
    }

    public function test_it_only_asks_for_the_fields_that_were_not_passed(): void
    {
        $this->artisan('user:create', ['username' => 'partial', '--rank' => 'power', '--password' => 'correct-horse-battery'])
            ->expectsQuestion('Display name', '')
            ->expectsQuestion('Email address', '')
            ->expectsConfirmation('Create partial as power?', 'yes')
            ->assertSuccessful();

        $user = User::query()->where('username', 'partial')->sole();

        $this->assertSame(UserRank::Power, $user->rank);
        $this->assertNull($user->display_name);
        $this->assertNull($user->email);
    }

    public function test_it_creates_nothing_when_the_confirmation_is_declined(): void
    {
        $this->artisan('user:create', ['username' => 'shy', '--password' => 'correct-horse-battery'])
            ->expectsQuestion('Display name', '')
            ->expectsQuestion('Email address', '')
            ->expectsQuestion('Rank', 'regular')
            ->expectsConfirmation('Create shy as regular?', 'no')
            ->assertFailed();

        $this->assertSame(0, User::query()->where('username', 'shy')->count());
    }

    public function test_it_fails_on_a_password_mismatch(): void
    {
        $this->artisan('user:create', ['username' => 'fumble'])
            ->expectsQuestion('Display name', '')
            ->expectsQuestion('Email address', '')
            ->expectsQuestion('Rank', 'regular')
            ->expectsQuestion('Password', 'correct-horse-battery')
            ->expectsQuestion('Confirm password', 'correct-horse-batteries')
            ->assertFailed();

        $this->assertSame(0, User::query()->where('username', 'fumble')->count());
    }

    public function test_it_asks_nothing_and_fails_without_a_username_when_not_interactive(): void
    {
        $this->artisan('user:create', ['--no-interaction' => true])->assertFailed();

        $this->assertSame(0, User::query()->count());
    }
}
