<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

final class SendPasswordResetLinkController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        // The broker's own answer is discarded on purpose: a known and an
        // unknown address must be indistinguishable from the outside.
        Password::broker()->sendResetLink($validated);

        return redirect()->route('login')->with('success', __('auth::password.sent'));
    }
}
