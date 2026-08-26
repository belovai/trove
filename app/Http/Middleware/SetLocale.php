<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /**
     * User preference, then Accept-Language, then the site default.
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolve($request));

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        /** @var list<string> $supported */
        $supported = config('trove.locales');

        $preference = $request->user()?->locale;

        if (is_string($preference) && in_array($preference, $supported, true)) {
            return $preference;
        }

        return $request->getPreferredLanguage($supported) ?? config('app.locale');
    }
}
