<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Models\User;

final class UnbanUser
{
    public function handle(User $user): User
    {
        $user->banned_at = null;
        $user->ban_reason = null;
        $user->save();

        return $user;
    }
}
