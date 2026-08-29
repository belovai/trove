<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class ShowForgotPasswordController
{
    public function __invoke(): Response
    {
        return Inertia::render('auth/ForgotPassword');
    }
}
