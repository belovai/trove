<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class ShowAccountController
{
    public function __invoke(): Response
    {
        return Inertia::render('account/Edit', [
            'locales' => config('trove.locales'),
        ]);
    }
}
