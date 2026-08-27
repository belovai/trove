<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;

final class CreateMediaController
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->can('media.upload'), 403);

        return Inertia::render('media/Create', [
            'visibilities' => array_column(Visibility::cases(), 'value'),
            'safety_ratings' => array_column(SafetyRating::cases(), 'value'),
            'max_filesize' => config('trove.media.max_filesize'),
            'allowed_mimes' => config('trove.media.allowed_mimes'),
        ]);
    }
}
