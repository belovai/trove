<?php

declare(strict_types=1);

namespace Modules\Setting\Exceptions;

use RuntimeException;

final class UnknownSettingException extends RuntimeException
{
    public static function for(string $key): self
    {
        return new self("No setting is declared under the key [{$key}].");
    }
}
