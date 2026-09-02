<?php

declare(strict_types=1);

namespace Modules\User\Console;

use Modules\User\Actions\GenerateUserPassword;

/**
 * The recovery path when nobody can sign in to the web UI to run one: the
 * install has no working mail, or the only administrator lost their password.
 */
final class ResetUserPasswordCommand extends UserCommand
{
    protected $signature = 'user:password
        {username : The account to reset}
        {--password= : Leave out to generate a 16-character password}';

    protected $description = 'Set or generate a new password for a user account.';

    public function handle(GenerateUserPassword $generatePassword): int
    {
        $user = $this->findUser();

        if ($user === null) {
            return self::FAILURE;
        }

        $given = $this->stringOption('password');
        $password = $generatePassword->handle($user, $given);

        $this->components->info("Password changed for {$user->username}.");

        if ($given === null) {
            $this->components->twoColumnDetail('Generated password', $password);
            $this->components->warn('Shown once. Store it now.');
        }

        return self::SUCCESS;
    }
}
