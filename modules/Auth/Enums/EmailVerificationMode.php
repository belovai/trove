<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum EmailVerificationMode: string
{
    /** No verification message is sent and nothing is asked of the user. */
    case Off = 'off';

    /** The message is sent and the state is shown, but nothing is blocked. */
    case Soft = 'soft';

    /** Write actions additionally require a verified address. */
    case Required = 'required';
}
