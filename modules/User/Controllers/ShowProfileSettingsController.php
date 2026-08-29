<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use App\Support\SettingsSections;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowProfileSettingsController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'sections' => SettingsSections::for($request->user()),
            'current' => 'profile',
        ]);
    }
}
