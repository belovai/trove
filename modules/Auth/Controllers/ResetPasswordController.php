<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Requests\ResetPasswordRequest;
use Modules\User\Models\User;

final class ResetPasswordController
{
    public function __invoke(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->password = $password;
                $user->setRememberToken(Str::random(60));

                // Opening the emailed link proves control of the address, so
                // a reset also settles verification.
                if ($user->email_verified_at === null) {
                    $user->email_verified_at = now();
                }

                $user->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return redirect('/login')->with('success', __('auth::password.reset'));
    }
}
