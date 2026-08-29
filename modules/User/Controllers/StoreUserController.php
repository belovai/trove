<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\User\Actions\CreateUser;
use Modules\User\Enums\UserRank;
use Modules\User\Requests\StoreUserRequest;

final class StoreUserController
{
    public function __construct(
        private readonly CreateUser $createUser,
    ) {}

    public function __invoke(StoreUserRequest $request): RedirectResponse
    {
        $this->createUser->handle(
            username: $request->string('username')->toString(),
            password: $request->string('password')->toString(),
            rank: UserRank::from($request->string('rank')->toString()),
            displayName: $request->input('display_name'),
            email: $request->input('email'),
        );

        return redirect()->route('settings.users')->with('success', __('user::account.user_created'));
    }
}
