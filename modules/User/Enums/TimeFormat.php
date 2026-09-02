<?php

declare(strict_types=1);

namespace Modules\User\Enums;

use App\Traits\EnumCompares;

/**
 * The clock layouts a user may pick from. Backing values are PHP date()
 * patterns, understood by the client formatter as well.
 */
enum TimeFormat: string
{
    use EnumCompares;

    case TwentyFour = 'H:i';
    case TwentyFourWithSeconds = 'H:i:s';
    case Twelve = 'g:i A';
    case TwelveWithSeconds = 'g:i:s A';
}
