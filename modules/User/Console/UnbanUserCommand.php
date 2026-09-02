<?php

declare(strict_types=1);

namespace Modules\User\Console;

use Modules\User\Actions\UnbanUser;

final class UnbanUserCommand extends UserCommand
{
    protected $signature = 'user:unban {username : The account to unban}';

    protected $description = 'Lift a ban from a user account.';

    public function handle(UnbanUser $unbanUser): int
    {
        $user = $this->findUser();

        if ($user === null) {
            return self::FAILURE;
        }

        $unbanUser->handle($user);

        $this->components->info("Unbanned {$user->username}.");

        return self::SUCCESS;
    }
}
