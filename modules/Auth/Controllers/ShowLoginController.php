<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class ShowLoginController
{
    public function __invoke(): Response
    {
        return Inertia::render('auth/Login', [
            'canRegister' => config('trove.registration.mode') === 'open',
        ]);
    }
}
