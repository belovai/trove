<?php

declare(strict_types=1);

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['username' => 'admin'],
            [
                'display_name' => 'Administrator',
                'email' => 'admin@trove.test',
                'email_verified_at' => now(),
                'password' => 'password',
                'rank' => UserRank::Administrator,
            ],
        );
    }
}
