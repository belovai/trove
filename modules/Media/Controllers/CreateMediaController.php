<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Setting\Facades\Settings;
use Modules\User\Models\User;

final class CreateMediaController
{
    public function __invoke(Request $request): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless($user?->can('media.upload'), 403);

        return Inertia::render('media/Create', [
            'visibilities' => array_column(Visibility::cases(), 'value'),
            'safety_ratings' => array_column(SafetyRating::cases(), 'value'),
            'max_filesize' => config('trove.media.max_filesize'),
            'allowed_mimes' => config('trove.media.allowed_mimes'),
            'default_visibility' => ($user->default_visibility ?? Settings::get('media.default_visibility'))->value,
        ]);
    }
}
