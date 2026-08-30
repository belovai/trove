<?php

declare(strict_types=1);

namespace Modules\User\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The only place in the application that knows what an avatar file path
 * looks like. Nothing else builds one.
 */
interface AvatarStorage
{
    /**
     * Overwrites any existing file at the user's path.
     */
    public function store(int $userId, string $contents): string;

    public function delete(string $path): void;

    public function exists(string $path): bool;

    public function stream(string $path, string $filename): StreamedResponse;
}
