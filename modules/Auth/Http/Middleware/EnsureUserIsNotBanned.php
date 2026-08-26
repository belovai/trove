<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Actions\LogoutUser;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsNotBanned
{
    public function __construct(
        private readonly LogoutUser $logoutUser,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isBanned()) {
            $this->logoutUser->handle();

            return redirect('/login')->with('error', __('auth::login.banned'));
        }

        return $next($request);
    }
}
