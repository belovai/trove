<?php

declare(strict_types=1);

namespace Modules\Tag\Enums;

use App\Traits\EnumCompares;

enum TagSource: string
{
    use EnumCompares;

    /** Someone typed it. Authoritative: never overwritten or removed by the resolver. */
    case Human = 'human';

    /** Derived from an implication. Recomputed wholesale on every change. */
    case Implied = 'implied';

    /** Reserved for auto-tagging. Nothing writes this yet. */
    case Ai = 'ai';
}
