<?php

declare(strict_types=1);

namespace Modules\User\Console;

use Modules\User\Actions\BanUser;

/**
 * The console counterpart of the ban toggle, and the only way to ban an
 * account the web policy protects — an administrator, most of all a
 * compromised one. Like every command here it runs no policy check: whoever
 * reaches this shell already has the database.
 */
final class BanUserCommand extends UserCommand
{
    protected $signature = 'user:ban
        {username : The account to ban}
        {--reason= : Shown to the user in the notice}';

    protected $description = 'Ban a user account.';

    public function handle(BanUser $banUser): int
    {
        $user = $this->findUser();

        if ($user === null) {
            return self::FAILURE;
        }

        $banUser->handle($user, $this->stringOption('reason'));

        $this->components->info("Banned {$user->username}.");

        return self::SUCCESS;
    }
}
