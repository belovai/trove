<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Media\Enums\SafetyRating;
use Modules\User\Actions\UpdateAccount;
use Modules\User\Models\User;
use Modules\User\Requests\UpdateAccountRequest;

final class UpdateAccountController
{
    public function __construct(
        private readonly UpdateAccount $updateAccount,
    ) {}

    public function __invoke(UpdateAccountRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->updateAccount->handle(
            user: $user,
            displayName: $request->input('display_name'),
            locale: $request->input('locale'),
            defaultSafetyFilter: $request->enum('default_safety_filter', SafetyRating::class),
        );

        return redirect()->route('account.show')->with('success', __('user::account.saved'));
    }
}
