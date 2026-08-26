<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\User\Actions\ChangeAccountPassword;
use Modules\User\Models\User;
use Modules\User\Requests\ChangeAccountPasswordRequest;

final class ChangeAccountPasswordController
{
    public function __construct(
        private readonly ChangeAccountPassword $changeAccountPassword,
    ) {}

    public function __invoke(ChangeAccountPasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->changeAccountPassword->handle(
            user: $user,
            password: $request->string('password')->toString(),
        );

        return redirect()->route('account.show')
            ->with('success', __('user::account.password_changed'));
    }
}
