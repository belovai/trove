<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | The locales the interface is translated into. The first entry is not
    | special; the fallback is config('app.locale'). SetLocale intersects the
    | Accept-Language header with this list.
    |
    */

    'locales' => ['en', 'hu'],

    /*
    |--------------------------------------------------------------------------
    | Registration
    |--------------------------------------------------------------------------
    |
    | mode:     open   - anyone may register
    |           closed - the registration routes are not registered at all
    |
    | email:    optional - the field is shown and may be left empty
    |           required - the field is shown and must be filled
    |           off      - the field is not shown and is not accepted
    |
    | approval: true  - new accounts are created with the Restricted rank and
    |                   an administrator promotes them
    |           false - new accounts are created with the Regular rank
    |
    | A user without an email address has no password reset: Laravel's reset
    | flow is email-based. They can change their password while logged in; if
    | they forget it, an administrator sets a new one. This is deliberate.
    |
    */

    'registration' => [
        'mode' => env('TROVE_REGISTRATION_MODE', 'open'),
        'email' => env('TROVE_REGISTRATION_EMAIL', 'optional'),
        'approval' => (bool) env('TROVE_REGISTRATION_APPROVAL', false),
    ],

];
