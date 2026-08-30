<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Enums\RegistrationEmailPolicy;
use Modules\Setting\Facades\Settings;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_register_page_renders(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_a_visitor_can_register_without_an_email(): void
    {
        $this->post('/register', [
            'username' => 'rp',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertRedirect('/');

        $user = User::query()->where('username', 'rp')->firstOrFail();

        $this->assertNull($user->email);
        $this->assertTrue($user->rank->equals(UserRank::Regular));
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_visitor_can_register_with_an_email(): void
    {
        $this->post('/register', [
            'username' => 'rp',
            'email' => 'rp@example.test',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertRedirect('/');

        $this->assertSame(
            'rp@example.test',
            User::query()->where('username', 'rp')->value('email'),
        );
    }

    public function test_the_email_is_required_when_the_policy_demands_it(): void
    {
        Settings::set('registration.email', RegistrationEmailPolicy::Required);

        $this->post('/register', [
            'username' => 'rp',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertSessionHasErrors('email');
    }

    public function test_the_email_is_rejected_when_the_policy_turns_it_off(): void
    {
        Settings::set('registration.email', RegistrationEmailPolicy::Off);

        $this->post('/register', [
            'username' => 'rp',
            'email' => 'rp@example.test',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertSessionHasErrors('email');
    }

    public function test_approval_mode_creates_a_restricted_account(): void
    {
        Settings::set('registration.approval', true);

        $this->post('/register', [
            'username' => 'rp',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ]);

        $this->assertTrue(
            User::query()->where('username', 'rp')->firstOrFail()->rank->equals(UserRank::Restricted),
        );
    }

    public function test_a_duplicate_username_is_rejected(): void
    {
        User::factory()->create(['username' => 'rp']);

        $this->post('/register', [
            'username' => 'rp',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertSessionHasErrors('username');
    }

    public function test_a_blocked_username_is_rejected_case_insensitively(): void
    {
        Settings::set('registration.blocked_names', ['Anonymous']);

        $this->post('/register', [
            'username' => 'ANONYMOUS',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertSessionHasErrors('username');
    }

    public function test_a_short_password_is_rejected(): void
    {
        $this->post('/register', [
            'username' => 'rp',
            'password' => 'short1',
            'password_confirmation' => 'short1',
        ])->assertSessionHasErrors('password');
    }
}
