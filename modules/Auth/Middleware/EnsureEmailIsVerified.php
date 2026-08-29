<?php

declare(strict_types=1);

namespace Modules\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Enums\EmailVerificationMode;
use Modules\Setting\Facades\Settings;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laravel's own "verified" middleware is unconditional. This one asks the
 * setting first, so the same route list serves all three modes.
 */
final class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Settings::get('registration.verify') !== EmailVerificationMode::Required) {
            return $next($request);
        }

        $user = $request->user();

        if ($user === null || $user->hasVerifiedEmail()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, __('auth::verification.banner'));
        }

        // A user with no address lands on the same screen: it asks for an
        // address instead of a click.
        return redirect()->route('verification.notice');
    }
}
