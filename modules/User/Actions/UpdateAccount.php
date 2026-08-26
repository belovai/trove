<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Models\User;

final class UpdateAccount
{
    public function handle(User $user, ?string $displayName, ?string $locale): User
    {
        $user->fill([
            // An empty string means "use my username", same as null.
            'display_name' => $displayName === '' ? null : $displayName,
            'locale' => $locale,
        ])->save();

        return $user;
    }
}
