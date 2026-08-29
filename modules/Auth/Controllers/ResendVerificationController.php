<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ResendVerificationController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Nothing to do for a verified or address-less account, and the
        // response is the same either way: no state is disclosed.
        if ($user !== null && $user->email !== null && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('success', __('auth::verification.sent'));
    }
}
