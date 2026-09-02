<?php

declare(strict_types=1);

namespace Modules\User\Policies;

use Modules\User\Enums\UserRank;
use Modules\User\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('user.manage');
    }

    /**
     * The whole administrative write path — display name, email, rank, ban,
     * generated password — passes through here, so the rule is one rule: you
     * never act on yourself (the account section is for that), and you never
     * act on your own rank or above. Strictly above, not "or equals": an
     * administrator editing another administrator is a takeover, since a
     * generated password hands over the account.
     *
     * That leaves a compromised administrator unreachable from the web on
     * purpose. The escape hatch is the console (`user:ban`, `user:rank`,
     * `user:password`), which needs server access and answers to no policy.
     */
    public function update(User $user, User $target): bool
    {
        if (!$user->can('user.manage')) {
            return false;
        }

        if ($user->id === $target->id) {
            return false;
        }

        return $user->rank->outranks($target->rank);
    }

    /**
     * A rank may never be raised above the rank of whoever assigns it.
     */
    public function assignRank(User $user, UserRank $rank): bool
    {
        return $user->rank->outranksOrEquals($rank);
    }
}
