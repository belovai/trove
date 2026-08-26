<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Auth\Actions\AuthenticateUser;
use Modules\Auth\Requests\LoginRequest;

final class LoginController
{
    public function __construct(
        private readonly AuthenticateUser $authenticateUser,
    ) {}

    public function __invoke(LoginRequest $request): RedirectResponse
    {
        $this->authenticateUser->handle(
            username: $request->string('username')->toString(),
            password: $request->string('password')->toString(),
            remember: $request->boolean('remember'),
        );

        return redirect()->intended('/');
    }
}
