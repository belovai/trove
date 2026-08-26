<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Actions\RegisterUser;
use Modules\Auth\Requests\RegisterRequest;
use Modules\User\Enums\UserRank;

final class RegisterController
{
    public function __construct(
        private readonly RegisterUser $registerUser,
    ) {}

    public function __invoke(RegisterRequest $request): RedirectResponse
    {
        $user = $this->registerUser->handle(
            username: $request->string('username')->toString(),
            password: $request->string('password')->toString(),
            email: $request->filled('email')
                ? $request->string('email')->toString()
                : null,
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')->with(
            'success',
            $user->rank->equals(UserRank::Restricted)
                ? __('auth::register.pending')
                : null,
        );
    }
}
