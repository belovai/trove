<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia;
use Modules\Auth\Notifications\ResetPassword;
use Modules\Setting\Facades\Settings;
use Modules\User\Models\User;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_link_is_sent_for_a_known_address(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->post('/forgot-password', ['email' => 'user@example.com'])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_an_unknown_address_gets_the_same_answer_and_no_message(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_an_unverified_address_still_gets_a_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => null]);

        $this->post('/forgot-password', ['email' => 'user@example.com'])->assertRedirect();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_a_valid_token_resets_the_password_and_confirms_the_address(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => null]);
        $token = Password::broker()->createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'brand-new-passphrase1',
            'password_confirmation' => 'brand-new-passphrase1',
        ])->assertRedirect('/login');

        $user->refresh();

        $this->assertTrue(Hash::check('brand-new-passphrase1', $user->password));
        // Opening the link proved control of the address.
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'user@example.com',
            'password' => 'brand-new-passphrase1',
            'password_confirmation' => 'brand-new-passphrase1',
        ])->assertSessionHasErrors('email');
    }

    public function test_the_login_page_hides_the_link_when_mail_cannot_be_delivered(): void
    {
        Settings::set('mail.enabled', false);

        $this->get('/login')->assertInertia(
            fn (AssertableInertia $page) => $page->where('canResetPassword', false),
        );
    }

    public function test_the_login_page_shows_the_link_when_mail_is_deliverable(): void
    {
        Settings::set('mail.enabled', true);
        Settings::set('mail.transport', 'smtp');
        Settings::set('mail.smtp.host', 'mailpit');

        $this->get('/login')->assertInertia(
            fn (AssertableInertia $page) => $page->where('canResetPassword', true),
        );
    }
}
