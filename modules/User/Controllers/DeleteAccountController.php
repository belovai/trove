<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\User\Actions\DeleteAccount;
use Modules\User\Models\User;
use Modules\User\Requests\DeleteAccountRequest;

final class DeleteAccountController
{
    public function __construct(
        private readonly DeleteAccount $deleteAccount,
    ) {}

    public function __invoke(DeleteAccountRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->deleteAccount->handle($user);

        return redirect('/')->with('success', __('user::account.deleted'));
    }
}
