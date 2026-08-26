<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

final class LogoutUser
{
    public function handle(): void
    {
        Auth::logout();

        Session::invalidate();
        Session::regenerateToken();
    }
}
