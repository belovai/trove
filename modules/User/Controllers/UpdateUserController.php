<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\User\Actions\BanUser;
use Modules\User\Actions\ChangeUserRank;
use Modules\User\Actions\UnbanUser;
use Modules\User\Actions\UpdateUser;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Modules\User\Requests\UpdateUserRequest;

final class UpdateUserController
{
    public function __construct(
        private readonly UpdateUser $updateUser,
        private readonly ChangeUserRank $changeUserRank,
        private readonly BanUser $banUser,
        private readonly UnbanUser $unbanUser,
    ) {}

    public function __invoke(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($request->has('display_name') || $request->has('email')) {
            $this->updateUser->handle(
                user: $user,
                displayName: $request->input('display_name', $user->display_name),
                email: $request->input('email', $user->email),
            );
        }

        if ($request->has('rank')) {
            $this->changeUserRank->handle(
                user: $user,
                rank: UserRank::from($request->string('rank')->toString()),
            );
        }

        if ($request->has('is_banned')) {
            $request->boolean('is_banned')
                ? $this->banUser->handle($user, $request->input('ban_reason'))
                : $this->unbanUser->handle($user);
        }

        return redirect()->route('settings.users')->with('success', __('user::account.user_saved'));
    }
}
