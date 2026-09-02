<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\Auth\Enums\EmailVerificationMode;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Setting\Facades\Settings;
use Modules\User\Enums\DateFormat;
use Modules\User\Enums\TimeFormat;
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
        ?string $timezone = null,
        ?DateFormat $dateFormat = null,
        ?TimeFormat $timeFormat = null,
        ?SafetyRating $defaultSafetyFilter = null,
        ?Visibility $defaultVisibility = null,
        ?bool $showUnsafeContent = null,
        ?bool $showUploads = null,
        bool $touchesDisplayName = false,
        bool $touchesEmail = false,
        bool $touchesLocale = false,
        bool $touchesTimezone = false,
        bool $touchesDateFormat = false,
        bool $touchesTimeFormat = false,
        bool $touchesDefaultVisibility = false,
        bool $touchesShowUnsafeContent = false,
        bool $touchesShowUploads = false,
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

        // Null is a legitimate value for all three: "use the system default".
        if ($touchesTimezone) {
            $user->timezone = $timezone === '' ? null : $timezone;
        }

        if ($touchesDateFormat) {
            $user->date_format = $dateFormat;
        }

        if ($touchesTimeFormat) {
            $user->time_format = $timeFormat;
        }

        // Absent means "leave it alone" — there is no "no filter" state.
        if ($defaultSafetyFilter !== null) {
            $user->default_safety_filter = $defaultSafetyFilter;
        }

        // Unlike the safety filter, null here is a legitimate value ("use the
        // system default"), so a touches flag distinguishes it from absent.
        if ($touchesDefaultVisibility) {
            $user->default_visibility = $defaultVisibility;
        }

        // false is a legitimate value here too, so absence is tracked the
        // same way as the visibility default.
        if ($touchesShowUnsafeContent) {
            $user->show_unsafe_content = (bool) $showUnsafeContent;
        }

        // false is a legitimate value here too, so absence is tracked the
        // same way as the unsafe-content switch.
        if ($touchesShowUploads) {
            $user->show_uploads = (bool) $showUploads;
        }

        $user->save();

        if ($verificationNeeded && Settings::get('registration.verify') !== EmailVerificationMode::Off) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }
}
