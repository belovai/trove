<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Models\User;
use Modules\User\Notifications\AccountBanned;

final class BanUser
{
    public function handle(User $user, ?string $reason = null): User
    {
        $user->banned_at = now();
        $user->ban_reason = $reason === '' ? null : $reason;
        $user->save();

        if ($user->email !== null) {
            $user->notify(
                (new AccountBanned($user->ban_reason))->locale($user->locale ?? (string) config('app.locale')),
            );
        }

        return $user;
    }
}
