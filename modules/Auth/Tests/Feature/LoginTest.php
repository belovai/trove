<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia;
use Modules\User\Models\User;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('login');
    }

    public function test_the_login_page_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_a_user_can_sign_in_with_a_username(): void
    {
        $user = User::factory()->create([
            'username' => 'rp',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'username' => 'rp',
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_it_records_the_login_time(): void
    {
        $user = User::factory()->create([
            'username' => 'rp',
            'password' => 'password',
            'last_login_at' => null,
        ]);

        $this->post('/login', ['username' => 'rp', 'password' => 'password']);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        User::factory()->create(['username' => 'rp', 'password' => 'password']);

        $this->post('/login', ['username' => 'rp', 'password' => 'wrong'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_an_unknown_username_is_rejected(): void
    {
        $this->post('/login', ['username' => 'nobody', 'password' => 'password'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_a_banned_user_cannot_sign_in(): void
    {
        User::factory()->banned()->create([
            'username' => 'rp',
            'password' => 'password',
        ]);

        $this->post('/login', ['username' => 'rp', 'password' => 'password'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_it_regenerates_the_session_on_sign_in(): void
    {
        User::factory()->create(['username' => 'rp', 'password' => 'password']);

        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', ['username' => 'rp', 'password' => 'password']);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_it_throttles_repeated_attempts(): void
    {
        User::factory()->create(['username' => 'rp', 'password' => 'password']);

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['username' => 'rp', 'password' => 'wrong']);
        }

        $this->post('/login', ['username' => 'rp', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_a_user_banned_during_a_live_session_is_signed_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $user->forceFill(['banned_at' => now(), 'ban_reason' => 'Spam'])->save();

        $this->get('/')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_the_auth_prop_carries_the_user_and_abilities(): void
    {
        $user = User::factory()->administrator()->create(['username' => 'rp']);

        $this->actingAs($user)->get('/')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('auth.user.username', 'rp')
                ->where('auth.user.rank', 'administrator')
                ->where('auth.can', fn ($can) => $can['user.manage'] === true)
        );
    }

    public function test_a_guest_has_no_user_and_no_abilities(): void
    {
        $this->get('/')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('auth.user', null)
                ->where('auth.can', [])
        );
    }
}
