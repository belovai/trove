<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Modules\Auth\Enums\RegistrationMode;
use Modules\Setting\Facades\Settings;

final class ShowLoginController
{
    public function __invoke(): Response
    {
        return Inertia::render('auth/Login', [
            'canRegister' => Settings::get('registration.mode') === RegistrationMode::Open,
        ]);
    }
}
