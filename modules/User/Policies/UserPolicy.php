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
     * Three invariants beyond the gate: you never edit yourself here (the
     * account section is for that), and you never touch someone who outranks
     * you. The rank check is vacuous while the gate is Administrator-only —
     * it is what makes lowering the gate later safe.
     */
    public function update(User $user, User $target): bool
    {
        if (!$user->can('user.manage')) {
            return false;
        }

        if ($user->id === $target->id) {
            return false;
        }

        return $user->rank->outranksOrEquals($target->rank);
    }

    /**
     * A rank may never be raised above the rank of whoever assigns it.
     */
    public function assignRank(User $user, UserRank $rank): bool
    {
        return $user->rank->outranksOrEquals($rank);
    }
}
