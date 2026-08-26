<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\Auth\Actions\LogoutUser;
use Modules\User\Models\User;

final class DeleteAccount
{
    public function __construct(
        private readonly LogoutUser $logoutUser,
    ) {}

    /**
     * Soft delete: the row stays, so the username remains reserved and
     * existing attribution keeps resolving.
     */
    public function handle(User $user): void
    {
        $user->delete();

        $this->logoutUser->handle();
    }
}
