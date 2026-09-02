<?php

declare(strict_types=1);

return [
    'pending_subject' => 'An account is waiting for approval on :app',
    'pending_line' => 'The account ":username" has registered and is waiting for approval.',
    'pending_action' => 'Open the user list',

    'approved_subject' => 'Your :app account has been approved',
    'approved_line' => 'Your account on :app has been approved. You can sign in and start using it.',
    'approved_action' => 'Sign in',

    'banned_subject' => 'Your :app account has been suspended',
    'banned_line' => 'Your account on :app has been suspended and can no longer be used to sign in.',
    'banned_reason' => 'Reason: :reason',

    'password_reset_subject' => 'Your :app password has been changed',
    'password_reset_line' => 'An administrator has set a new password for your account on :app.',
    'password_reset_hint' => 'The new password is not in this email. Ask the administrator who changed it, or reset it yourself if you can still sign in.',
];
