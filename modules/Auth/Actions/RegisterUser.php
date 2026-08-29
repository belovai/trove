<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Modules\Setting\Facades\Settings;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;

final class RegisterUser
{
    public function handle(string $username, string $password, ?string $email = null): User
    {
        return User::query()->create([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'rank' => Settings::get('registration.approval')
                ? UserRank::Restricted
                : UserRank::Regular,
        ]);
    }
}
