<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum RegistrationMode: string
{
    /** Anyone may register. */
    case Open = 'open';

    /** The registration routes return 404. Existing accounts are unaffected. */
    case Closed = 'closed';
}
