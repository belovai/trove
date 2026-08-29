<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Notifications\VerifyEmail;
use Modules\User\Models\User;
use Tests\TestCase;

final class AccountEmailChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_address_is_unverified_and_gets_a_confirmation_message(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'old@example.com', 'email_verified_at' => now()]);

        $this->actingAs($user)
            ->patch('/account', ['email' => 'new@example.com'])
            ->assertRedirect();

        $user->refresh();

        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_saving_the_same_address_changes_nothing(): void
    {
        Notification::fake();
        $verifiedAt = now()->subDay();
        $user = User::factory()->create(['email' => 'same@example.com', 'email_verified_at' => $verifiedAt]);

        $this->actingAs($user)
            ->patch('/account', ['email' => 'same@example.com'])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()?->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_clearing_the_address_clears_the_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'old@example.com', 'email_verified_at' => now()]);

        $this->actingAs($user)
            ->patch('/account', ['email' => ''])
            ->assertRedirect();

        $user->refresh();

        $this->assertNull($user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertNothingSent();
    }
}
