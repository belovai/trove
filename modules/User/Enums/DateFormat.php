<?php

declare(strict_types=1);

namespace Modules\User\Enums;

use App\Traits\EnumCompares;

/**
 * The date layouts a user may pick from. The backing value is a PHP date()
 * pattern, and the client formatter understands the same tokens — this enum is
 * the only place the vocabulary is declared.
 */
enum DateFormat: string
{
    use EnumCompares;

    case Iso = 'Y-m-d';
    case DayMonthYearSlash = 'd/m/Y';
    case MonthDayYearSlash = 'm/d/Y';
    case DayMonthYearDot = 'd.m.Y.';
    case YearMonthDayDot = 'Y. m. d.';
    case DayMonthNameYear = 'j M Y';
    case MonthNameDayYear = 'M j, Y';
}
