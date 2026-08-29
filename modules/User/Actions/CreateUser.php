<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Support\Facades\Hash;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;

final class CreateUser
{
    public function handle(
        string $username,
        string $password,
        UserRank $rank,
        ?string $displayName = null,
        ?string $email = null,
    ): User {
        return User::query()->create([
            'username' => $username,
            'display_name' => $displayName === '' ? null : $displayName,
            'email' => $email === '' ? null : $email,
            'password' => Hash::make($password),
            'rank' => $rank,
        ]);
    }
}
