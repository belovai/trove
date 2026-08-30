<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use Modules\User\Contracts\AvatarStorage;
use Modules\User\Enums\AvatarSource;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

final class ServeAvatarController
{
    public function __construct(
        private readonly AvatarStorage $storage,
    ) {}

    public function __invoke(User $user): Response
    {
        abort_unless($user->avatar_source === AvatarSource::Upload && $user->avatar_path !== null, 404);

        abort_unless($this->storage->exists($user->avatar_path), 404);

        return $this->storage->stream($user->avatar_path, "{$user->username}-avatar.webp");
    }
}
