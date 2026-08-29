<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Enums\UserRank;
use Modules\User\Models\User;

final class ChangeUserRank
{
    /**
     * No side effects beyond the column: privileges are derived from the rank
     * on every request, so a demotion takes hold on the user's next one.
     */
    public function handle(User $user, UserRank $rank): User
    {
        $user->rank = $rank;
        $user->save();

        return $user;
    }
}
