<?php

declare(strict_types=1);

namespace Modules\Setting\Exceptions;

use RuntimeException;

final class DuplicateSettingException extends RuntimeException
{
    public static function for(string $key, string $first, string $second): self
    {
        return new self("The setting key [{$key}] is declared by both the [{$first}] and [{$second}] modules.");
    }
}
