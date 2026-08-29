<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\Media\Enums\SafetyRating;
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

        if ($touchesEmail) {
            $user->email = $email === '' ? null : $email;
        }

        if ($touchesLocale) {
            $user->locale = $locale;
        }

        // Absent means "leave it alone" — there is no "no filter" state.
        if ($defaultSafetyFilter !== null) {
            $user->default_safety_filter = $defaultSafetyFilter;
        }

        $user->save();

        return $user;
    }
}
