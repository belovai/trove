<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Support\Str;
use Modules\Mail\Support\MailConfigurator;
use Modules\User\Models\User;
use Modules\User\Notifications\PasswordResetByAdmin;

/**
 * An administrator replacing someone else's password, from the user list or
 * from the console. The plaintext is returned rather than mailed: an install
 * with no address on the account, or with mail switched off, is exactly the
 * case this exists for, so the caller shows it once and it is never stored.
 */
final class GenerateUserPassword
{
    public function __construct(private readonly MailConfigurator $mail) {}

    public function handle(User $user, ?string $password = null): string
    {
        $password ??= Str::password(16);

        // A remember-me cookie survives a password change, which would leave
        // whoever the reset was aimed at still signed in.
        $user->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();

        if ($user->email !== null && $this->mail->isDeliverable()) {
            $user->notify(
                (new PasswordResetByAdmin)->locale($user->locale ?? (string) config('app.locale')),
            );
        }

        return $password;
    }
}
