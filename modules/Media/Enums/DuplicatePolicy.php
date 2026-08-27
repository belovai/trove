<?php

declare(strict_types=1);

namespace Modules\Media\Enums;

enum DuplicatePolicy: string
{
    case Warn = 'warn';
    case Reject = 'reject';
}
