<?php

declare(strict_types=1);

return [
    'section_system' => 'System',
    'title' => 'System settings',
    'saved' => 'The settings have been saved.',
    'save' => 'Save',

    'block_general' => 'General',
    'block_general_hint' => 'Basic identity of this installation.',
    'app_name' => 'Site name',
    'app_name_hint' => 'Shown in the browser tab, the header and on the landing page.',

    'block_registration' => 'Registration',
    'block_registration_hint' => 'Who may create an account, and what is asked of them.',
    'registration_mode' => 'Registration',
    'registration_mode_hint' => 'Closed hides the registration form and returns 404 for its routes. Existing accounts are unaffected.',
    'registration_mode_open' => 'Open — anyone may register',
    'registration_mode_closed' => 'Closed — nobody may register',
    'registration_email' => 'Email address',
    'registration_email_hint' => 'Whether new accounts are asked for an email address.',
    'registration_email_optional' => 'Optional — the field is shown and may be left empty',
    'registration_email_required' => 'Required — the field must be filled',
    'registration_email_off' => 'Off — the field is not shown',
    'registration_approval' => 'Approval required',
    'registration_approval_hint' => 'New accounts are created at the Restricted rank for an administrator to promote, instead of Regular.',
    'no_recovery_warning' => 'With email off and approval disabled, a user who forgets their password has no way to recover it: the reset flow is email-based. An administrator can set a new password for them.',

    'registration_verify' => 'Email confirmation',
    'registration_verify_hint' => 'Whether a new address has to be confirmed, and whether an unconfirmed one limits what the account can do.',
    'registration_verify_off' => 'Off — no confirmation message is sent',
    'registration_verify_soft' => 'Soft — confirmation is asked for, nothing is blocked',
    'registration_verify_required' => 'Required — uploading and tagging need a confirmed address',
];
