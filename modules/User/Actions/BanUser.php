<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Models\User;

final class BanUser
{
    public function handle(User $user, ?string $reason = null): User
    {
        $user->banned_at = now();
        $user->ban_reason = $reason === '' ? null : $reason;
        $user->save();

        return $user;
    }
}
