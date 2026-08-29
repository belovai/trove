<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Modules\Auth\Notifications\VerifyEmail;
use Modules\Setting\Facades\Settings;
use Modules\User\Models\User;
use Tests\TestCase;

final class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_with_an_address_sends_a_verification_message(): void
    {
        Notification::fake();

        $this->post('/register', [
            'username' => 'newcomer',
            'email' => 'newcomer@example.com',
            'password' => 'correct-horse-battery1',
            'password_confirmation' => 'correct-horse-battery1',
        ])->assertRedirect();

        Notification::assertSentTo(User::query()->where('username', 'newcomer')->firstOrFail(), VerifyEmail::class);
    }

    public function test_registering_without_an_address_sends_nothing(): void
    {
        Notification::fake();

        $this->post('/register', [
            'username' => 'quiet',
            'password' => 'correct-horse-battery1',
            'password_confirmation' => 'correct-horse-battery1',
        ])->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_verification_is_not_sent_when_the_mode_is_off(): void
    {
        Notification::fake();
        Settings::set('registration.verify', 'off');

        $this->post('/register', [
            'username' => 'newcomer',
            'email' => 'newcomer@example.com',
            'password' => 'correct-horse-battery1',
            'password_confirmation' => 'correct-horse-battery1',
        ])->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_a_signed_link_marks_the_address_verified(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => null]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1((string) $user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect('/settings/account');

        $this->assertNotNull($user->fresh()?->email_verified_at);
    }

    public function test_a_tampered_hash_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => null]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1('someone-else@example.com'),
        ]);

        $this->actingAs($user)->get($url)->assertForbidden();

        $this->assertNull($user->fresh()?->email_verified_at);
    }

    public function test_an_unsigned_link_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => null]);

        $this->actingAs($user)
            ->get("/verify-email/{$user->id}/".sha1((string) $user->email))
            ->assertForbidden();
    }

    public function test_a_user_can_ask_for_a_new_message(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => null]);

        $this->actingAs($user)->post('/email/verification-notification')->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_an_already_verified_user_gets_no_new_message(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => now()]);

        $this->actingAs($user)->post('/email/verification-notification')->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_a_user_without_an_address_gets_no_message(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => null, 'email_verified_at' => null]);

        $this->actingAs($user)->post('/email/verification-notification')->assertRedirect();

        Notification::assertNothingSent();
    }
}
