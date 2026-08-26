<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Auth\Actions\LogoutUser;

final class LogoutController
{
    public function __construct(
        private readonly LogoutUser $logoutUser,
    ) {}

    public function __invoke(): RedirectResponse
    {
        $this->logoutUser->handle();

        return redirect('/login')->with('success', __('auth::login.signed_out'));
    }
}
