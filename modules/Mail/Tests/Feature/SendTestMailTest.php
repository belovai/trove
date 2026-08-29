<?php

declare(strict_types=1);

namespace Modules\Mail\Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Mail\Notifications\TestMail;
use Modules\Setting\Facades\Settings;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class SendTestMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_regular_user_cannot_send_a_test_message(): void
    {
        $this->actingAs(User::factory()->create(['rank' => UserRank::Regular]))
            ->post('/settings/mail/test', ['email' => 'someone@example.com'])
            ->assertForbidden();
    }

    public function test_an_administrator_sends_a_test_message_to_the_given_address(): void
    {
        Notification::fake();
        Settings::set('mail.enabled', true);

        $this->actingAs(User::factory()->create(['rank' => UserRank::Administrator]))
            ->post('/settings/mail/test', ['email' => 'admin@example.com'])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentOnDemand(
            TestMail::class,
            fn (TestMail $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'admin@example.com',
        );
    }

    public function test_the_address_is_validated(): void
    {
        $this->actingAs(User::factory()->create(['rank' => UserRank::Administrator]))
            ->post('/settings/mail/test', ['email' => 'not-an-address'])
            ->assertSessionHasErrors('email');
    }

    public function test_a_transport_failure_is_reported_back_to_the_administrator(): void
    {
        Settings::set('mail.enabled', true);
        Settings::set('mail.transport', 'smtp');
        // Port 1 refuses connections, so the transport throws instead of
        // silently succeeding.
        Settings::set('mail.smtp.host', '127.0.0.1');
        Settings::set('mail.smtp.port', 1);
        Settings::set('mail.smtp.timeout', 1);

        $this->actingAs(User::factory()->create(['rank' => UserRank::Administrator]))
            ->post('/settings/mail/test', ['email' => 'admin@example.com'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_the_test_send_is_not_queued(): void
    {
        $this->assertNotInstanceOf(
            ShouldQueue::class,
            new TestMail,
        );
    }
}
