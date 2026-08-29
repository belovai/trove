<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Modules\Setting\Facades\Settings;

final class ShowRegisterController
{
    public function __invoke(): Response
    {
        return Inertia::render('auth/Register', [
            // 'optional' | 'required' | 'off' — the page draws the field
            // accordingly. The client never reads the environment.
            'emailPolicy' => Settings::get('registration.email')->value,
        ]);
    }
}
