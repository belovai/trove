<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\User\Actions\GenerateUserPassword;
use Modules\User\Models\User;

final class GenerateUserPasswordController
{
    public function __construct(private readonly GenerateUserPassword $generatePassword) {}

    public function __invoke(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->can('update', $user) ?? false, 403);

        $password = $this->generatePassword->handle($user);

        // Flashed, not rendered into the page payload: it survives exactly one
        // request, and a reload does not bring it back.
        return back()->with('generated_password', [
            'username' => $user->username,
            'password' => $password,
        ]);
    }
}
