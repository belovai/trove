<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use App\Support\DateTimeFormats;
use App\Support\SettingsSections;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Modules\User\Enums\DateFormat;
use Modules\User\Enums\TimeFormat;
use Modules\User\Models\User;

final class ShowAccountSettingsController
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('settings/Account', [
            'sections' => SettingsSections::for($user),
            'current' => 'account',
            'locales' => config('trove.locales'),
            'email' => $user->email,
            'visibilities' => array_column(Visibility::cases(), 'value'),
            'timezones' => DateTimeFormats::timezones(),
            'date_formats' => array_column(DateFormat::cases(), 'value'),
            'time_formats' => array_column(TimeFormat::cases(), 'value'),
            'stats' => [
                'registered_at' => $user->created_at?->toIso8601String(),
                'last_seen_at' => $user->last_login_at?->toIso8601String(),
                'rank' => $user->rank->value,
                'uploads' => Media::query()->where('user_id', $user->id)->count(),
                // Placeholders: no feature stands behind these yet. See
                // docs/design.md, "Placeholders".
                'favorites' => 0,
                'comments' => 0,
                'liked' => 0,
                'disliked' => 0,
            ],
        ]);
    }
}
