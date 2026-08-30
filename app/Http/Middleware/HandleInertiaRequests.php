<?php

namespace App\Http\Middleware;

use App\Support\Translations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;
use Modules\Media\Enums\SafetyRating;
use Modules\Setting\Facades\Settings;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() === null ? null : [
                    'username' => $request->user()->username,
                    'display_name' => $request->user()->displayName(),
                    'email' => $request->user()->email,
                    'email_verified' => $request->user()->hasVerifiedEmail(),
                    'rank' => $request->user()->rank->value,
                    'locale' => $request->user()->locale,
                    'default_safety_filter' => $request->user()->default_safety_filter,
                    'default_visibility' => $request->user()->default_visibility,
                    'show_unsafe_content' => $request->user()->show_unsafe_content,
                    'avatar_source' => $request->user()->avatar_source,
                    'avatar_url' => $request->user()->avatarUrl(),
                ],
                // The privilege map's abilities, each evaluated for this user, so the
                // client never re-implements the rank comparison.
                'can' => $this->abilities($request),
            ],
            'app_name' => Settings::get('app.name'),
            'locale' => $locale,
            'locales' => config('trove.locales'),
            // The rating vocabulary, in order. Both the account form and the
            // listing filter bar read it, so neither hardcodes the enum.
            'safety_ratings' => array_map(
                fn (SafetyRating $rating): string => $rating->value,
                SafetyRating::cases(),
            ),
            'translations' => app(Translations::class)->forLocale($locale),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function abilities(Request $request): array
    {
        $user = $request->user();

        if ($user === null) {
            return [];
        }

        $abilities = [];

        foreach (array_keys(Gate::abilities()) as $ability) {
            $abilities[$ability] = Gate::forUser($user)->allows($ability);
        }

        return $abilities;
    }
}
