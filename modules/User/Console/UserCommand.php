<?php

declare(strict_types=1);

namespace Modules\User\Console;

use Illuminate\Console\Command;
use Modules\User\Models\User;

/**
 * What the administrative commands share: resolving the account argument, and
 * the deliberate absence of a policy check. UserPolicy guards the HTTP path
 * only — the console operator already has the database and the application
 * key, and these commands exist precisely for the accounts that path refuses
 * to touch.
 */
abstract class UserCommand extends Command
{
    protected function findUser(): ?User
    {
        $username = (string) $this->argument('username');

        $user = User::query()->where('username', $username)->first();

        if ($user === null) {
            $this->components->error("No account named {$username}.");
        }

        return $user;
    }

    protected function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
