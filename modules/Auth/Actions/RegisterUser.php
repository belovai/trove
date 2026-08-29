<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Notification;
use Modules\Auth\Enums\EmailVerificationMode;
use Modules\Setting\Facades\Settings;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Modules\User\Notifications\PendingRegistration;

final class RegisterUser
{
    public function handle(string $username, string $password, ?string $email = null): User
    {
        $user = User::query()->create([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'rank' => Settings::get('registration.approval')
                ? UserRank::Restricted
                : UserRank::Regular,
        ]);

        // Queued: a mail failure must not turn a completed registration into
        // a 500 for someone who just signed up.
        if (Settings::get('registration.verify') !== EmailVerificationMode::Off) {
            $user->sendEmailVerificationNotification();
        }

        $adminAddress = trim((string) Settings::get('mail.admin_address'));

        // An empty administrator address means the notice is simply not sent;
        // it is not an error state.
        if (Settings::get('registration.approval') && $adminAddress !== '') {
            Notification::route('mail', $adminAddress)->notify(new PendingRegistration($user->username));
        }

        return $user;
    }
}
