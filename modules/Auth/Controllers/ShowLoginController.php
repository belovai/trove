<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Modules\Auth\Enums\RegistrationMode;
use Modules\Mail\Support\MailConfigurator;
use Modules\Setting\Facades\Settings;

final class ShowLoginController
{
    public function __invoke(MailConfigurator $configurator): Response
    {
        return Inertia::render('auth/Login', [
            'canRegister' => Settings::get('registration.mode') === RegistrationMode::Open,
            // A link to a flow that cannot deliver is worse than no link.
            'canResetPassword' => $configurator->isDeliverable(),
        ]);
    }
}
