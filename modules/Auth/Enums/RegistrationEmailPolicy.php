<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum RegistrationEmailPolicy: string
{
    /** The field is shown and may be left empty. */
    case Optional = 'optional';

    /** The field is shown and must be filled. */
    case Required = 'required';

    /** The field is not shown and is not accepted. */
    case Off = 'off';
}
