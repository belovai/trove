<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowVerifyEmailController
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user?->hasVerifiedEmail() === true) {
            return redirect('/settings/account');
        }

        return Inertia::render('auth/VerifyEmail', [
            'email' => $user?->email,
        ]);
    }
}
