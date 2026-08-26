<?php

declare(strict_types=1);

namespace Modules\User\Enums;

use App\Contracts\HasLevel;
use App\Traits\EnumCompares;

enum UserRank: string implements HasLevel
{
    use EnumCompares;

    case Restricted = 'restricted';
    case Regular = 'regular';
    case Power = 'power';
    case Moderator = 'moderator';
    case Administrator = 'administrator';

    /**
     * The only ordering mechanism. Nothing else compares ranks.
     */
    public function level(): int
    {
        return match ($this) {
            self::Restricted => 1,
            self::Regular => 2,
            self::Power => 3,
            self::Moderator => 4,
            self::Administrator => 5,
        };
    }

    public function label(): string
    {
        return __("user::rank.{$this->value}");
    }

    public function outranksOrEquals(self $other): bool
    {
        return $this->level() >= $other->level();
    }
}
