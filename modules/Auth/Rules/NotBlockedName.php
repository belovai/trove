<?php

declare(strict_types=1);

namespace Modules\Auth\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Setting\Facades\Settings;

/**
 * Rejects a value that exactly matches one of the administrator-configured
 * `registration.blocked_names`, case-insensitively. Shared by username and
 * display name fields across registration, self-service account edit, and
 * admin user management.
 */
final class NotBlockedName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        /** @var list<string> $blockedNames */
        $blockedNames = Settings::get('registration.blocked_names');

        $normalized = mb_strtolower(trim($value));

        foreach ($blockedNames as $blockedName) {
            if ($normalized === mb_strtolower(trim($blockedName))) {
                $fail(__('auth::validation.blocked_name'));

                return;
            }
        }
    }
}
