<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Setting\Facades\Settings;
use Modules\User\Actions\BanUser;
use Modules\User\Actions\ChangeUserRank;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Modules\User\Notifications\AccountApproved;
use Modules\User\Notifications\AccountBanned;
use Modules\User\Notifications\PendingRegistration;
use Tests\TestCase;

final class AccountNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_registration_awaiting_approval_notifies_the_administrator_address(): void
    {
        Notification::fake();
        Settings::set('registration.approval', true);
        Settings::set('mail.admin_address', 'admin@example.com');

        $this->post('/register', [
            'username' => 'newcomer',
            'password' => 'correct-horse-battery1',
            'password_confirmation' => 'correct-horse-battery1',
        ])->assertRedirect();

        Notification::assertSentOnDemand(
            PendingRegistration::class,
            fn (PendingRegistration $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'admin@example.com',
        );
    }

    public function test_nothing_is_sent_without_an_administrator_address(): void
    {
        Notification::fake();
        Settings::set('registration.approval', true);
        Settings::set('mail.admin_address', '');

        $this->post('/register', [
            'username' => 'newcomer',
            'password' => 'correct-horse-battery1',
            'password_confirmation' => 'correct-horse-battery1',
        ])->assertRedirect();

        // The registration carries no address either, so nothing at all goes out.
        Notification::assertNothingSent();
    }

    public function test_nothing_is_sent_when_approval_is_off(): void
    {
        Notification::fake();
        Settings::set('registration.approval', false);
        Settings::set('mail.admin_address', 'admin@example.com');

        $this->post('/register', [
            'username' => 'newcomer',
            'password' => 'correct-horse-battery1',
            'password_confirmation' => 'correct-horse-battery1',
        ])->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_raising_a_restricted_user_notifies_them(): void
    {
        Notification::fake();
        $user = User::factory()->create(['rank' => UserRank::Restricted, 'email' => 'user@example.com']);

        app(ChangeUserRank::class)->handle($user, UserRank::Regular);

        Notification::assertSentTo($user, AccountApproved::class);
    }

    public function test_a_rank_change_between_higher_ranks_notifies_nobody(): void
    {
        Notification::fake();
        $user = User::factory()->create(['rank' => UserRank::Regular, 'email' => 'user@example.com']);

        app(ChangeUserRank::class)->handle($user, UserRank::Moderator);

        Notification::assertNothingSent();
    }

    public function test_a_user_without_an_address_is_not_notified(): void
    {
        Notification::fake();
        $user = User::factory()->create(['rank' => UserRank::Restricted, 'email' => null]);

        app(ChangeUserRank::class)->handle($user, UserRank::Regular);

        Notification::assertNothingSent();
    }

    public function test_banning_a_user_notifies_them_with_the_reason(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);

        app(BanUser::class)->handle($user, 'Repeated policy violations');

        Notification::assertSentTo(
            $user,
            fn (AccountBanned $notification): bool => $notification->reason === 'Repeated policy violations',
        );
    }
}
