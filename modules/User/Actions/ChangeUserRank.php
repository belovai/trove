<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Modules\User\Notifications\AccountApproved;

final class ChangeUserRank
{
    /**
     * No side effects beyond the column: privileges are derived from the rank
     * on every request, so a demotion takes hold on the user's next one.
     */
    public function handle(User $user, UserRank $rank): User
    {
        // Captured before the write: "was restricted, now is not" is what
        // approval means here, and a promotion between two higher ranks is
        // not an approval.
        $wasRestricted = $user->rank === UserRank::Restricted;

        $user->rank = $rank;
        $user->save();

        if ($wasRestricted && $rank !== UserRank::Restricted && $user->email !== null) {
            $user->notify((new AccountApproved)->locale($user->locale ?? (string) config('app.locale')));
        }

        return $user;
    }
}
