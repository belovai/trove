<?php

declare(strict_types=1);

use Modules\Auth\Enums\EmailVerificationMode;
use Modules\Auth\Enums\RegistrationEmailPolicy;
use Modules\Auth\Enums\RegistrationMode;
use Modules\Setting\Support\SettingDefinition;

/*
 * env() supplies the default only. Once an administrator saves a value the
 * database wins and the environment variable is ignored, so TROVE_REGISTRATION_*
 * is an initial value for a fresh install, not a live control.
 *
 * A user without an email address has no password reset: Laravel's reset flow
 * is email-based. They can change their password while logged in; if they
 * forget it, an administrator sets a new one. This is deliberate, and the
 * settings page says so.
 */

return [
    'registration.mode' => SettingDefinition::enum(
        RegistrationMode::class,
        (string) env('TROVE_REGISTRATION_MODE', 'open'),
    ),

    'registration.email' => SettingDefinition::enum(
        RegistrationEmailPolicy::class,
        (string) env('TROVE_REGISTRATION_EMAIL', 'optional'),
    ),

    'registration.approval' => SettingDefinition::bool(
        (bool) env('TROVE_REGISTRATION_APPROVAL', false),
    ),

    'registration.verify' => SettingDefinition::enum(
        EmailVerificationMode::class,
        (string) env('TROVE_REGISTRATION_VERIFY', 'soft'),
    ),
];
