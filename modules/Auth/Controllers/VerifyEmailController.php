<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class VerifyEmailController
{
    public function __invoke(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = $request->user();

        // The signature proves the link was issued by us; these two checks
        // prove it was issued for this account and this address.
        abort_unless($user !== null && (int) $user->getKey() === $id, 403);
        abort_unless(hash_equals(sha1((string) $user->getEmailForVerification()), $hash), 403);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect('/settings/account')->with('success', __('auth::verification.verified'));
    }
}
