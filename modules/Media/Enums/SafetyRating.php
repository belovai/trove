<?php

declare(strict_types=1);

namespace Modules\Media\Enums;

use App\Contracts\HasLevel;
use App\Traits\EnumCompares;

enum SafetyRating: string implements HasLevel
{
    use EnumCompares;

    case Safe = 'safe';
    case Sketchy = 'sketchy';
    case Unsafe = 'unsafe';

    /**
     * The only ordering mechanism. Nothing else compares safety ratings.
     */
    public function level(): int
    {
        return match ($this) {
            self::Safe => 1,
            self::Sketchy => 2,
            self::Unsafe => 3,
        };
    }

    /**
     * Whether an item at this rating is shown to a viewer whose filter is
     * $filter. This is a display decision, never an access decision.
     */
    public function isWithin(self $filter): bool
    {
        return $this->level() <= $filter->level();
    }

    public function label(): string
    {
        return __("media::safety.{$this->value}");
    }
}
