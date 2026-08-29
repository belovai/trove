<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Models\User;

final class UpdateUser
{
    public function handle(User $user, ?string $displayName, ?string $email): User
    {
        $user->fill([
            'display_name' => $displayName === '' ? null : $displayName,
            'email' => $email === '' ? null : $email,
        ])->save();

        return $user;
    }
}
