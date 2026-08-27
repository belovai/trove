<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use Modules\Media\Models\Media;
use RuntimeException;

final class HashIdGenerator
{
    private const LENGTH = 10;

    private const ATTEMPTS = 10;

    /**
     * A 10-character base62 id, verified free. withTrashed() matters: a
     * soft-deleted item keeps its id reserved, so old links never resolve to
     * a different item.
     */
    public function generate(): string
    {
        for ($attempt = 0; $attempt < self::ATTEMPTS; $attempt++) {
            $candidate = $this->candidate();

            if (!Media::withTrashed()->where('hash_id', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException('Could not generate a free media hash id.');
    }

    private function candidate(): string
    {
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $id = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $id .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $id;
    }
}
