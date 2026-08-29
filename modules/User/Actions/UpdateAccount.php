<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\Auth\Enums\EmailVerificationMode;
use Modules\Media\Enums\SafetyRating;
use Modules\Setting\Facades\Settings;
use Modules\User\Models\User;

final class UpdateAccount
{
    /**
     * Every parameter is optional and null means "not submitted": the account
     * and profile sections send different subsets of the same form.
     */
    public function handle(
        User $user,
        ?string $displayName = null,
        ?string $email = null,
        ?string $locale = null,
        ?SafetyRating $defaultSafetyFilter = null,
        bool $touchesDisplayName = false,
        bool $touchesEmail = false,
        bool $touchesLocale = false,
    ): User {
        if ($touchesDisplayName) {
            // An empty string means "use my username", same as null.
            $user->display_name = $displayName === '' ? null : $displayName;
        }

        $verificationNeeded = false;

        if ($touchesEmail) {
            $newEmail = $email === '' ? null : $email;

            // A changed address is a different address: the old confirmation
            // says nothing about it.
            if ($newEmail !== $user->email) {
                $user->email = $newEmail;
                $user->email_verified_at = null;
                $verificationNeeded = $newEmail !== null;
            }
        }

        if ($touchesLocale) {
            $user->locale = $locale;
        }

        // Absent means "leave it alone" — there is no "no filter" state.
        if ($defaultSafetyFilter !== null) {
            $user->default_safety_filter = $defaultSafetyFilter;
        }

        $user->save();

        if ($verificationNeeded && Settings::get('registration.verify') !== EmailVerificationMode::Off) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }
}
