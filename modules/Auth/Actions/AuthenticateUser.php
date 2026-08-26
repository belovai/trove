<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Modules\User\Models\User;

final class AuthenticateUser
{
    /**
     * @throws ValidationException
     */
    public function handle(string $username, string $password, bool $remember = false): User
    {
        $user = User::query()->where('username', $username)->first();

        if ($user === null || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => __('auth::login.failed'),
            ]);
        }

        if ($user->isBanned()) {
            throw ValidationException::withMessages([
                'username' => __('auth::login.banned'),
            ]);
        }

        Auth::login($user, $remember);
        Session::regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }
}
