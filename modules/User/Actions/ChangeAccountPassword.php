<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Support\Facades\Session;
use Modules\User\Models\User;

final class ChangeAccountPassword
{
    public function handle(User $user, string $password): void
    {
        $user->forceFill(['password' => $password])->save();

        // Keep the current session valid, invalidate any other.
        Session::regenerate();
    }
}
