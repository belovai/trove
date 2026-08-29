<?php

declare(strict_types=1);

namespace Modules\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Enums\RegistrationMode;
use Modules\Setting\Facades\Settings;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registration mode is a runtime setting, so it cannot decide which routes get
 * registered: route:cache would freeze it, and every console command would read
 * the database while loading routes. The routes always exist; this middleware
 * decides whether they answer.
 */
final class EnsureRegistrationIsOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            Settings::get('registration.mode') === RegistrationMode::Open,
            404,
        );

        return $next($request);
    }
}
