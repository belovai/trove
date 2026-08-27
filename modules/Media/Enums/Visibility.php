<?php

declare(strict_types=1);

namespace Modules\Media\Enums;

use App\Traits\EnumCompares;

enum Visibility: string
{
    use EnumCompares;

    case Public = 'public';
    case Authenticated = 'authenticated';
    case Unlisted = 'unlisted';
    case Private = 'private';

    /**
     * Whether the item appears in listings at all. Unlisted items are
     * reachable by link but never listed.
     */
    public function isListable(): bool
    {
        return $this !== self::Unlisted;
    }

    public function label(): string
    {
        return __("media::visibility.{$this->value}");
    }
}
