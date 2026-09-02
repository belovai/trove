<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
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
            email: $request->input('email'),
            locale: $request->input('locale'),
            defaultSafetyFilter: $request->enum('default_safety_filter', SafetyRating::class),
            defaultVisibility: $request->enum('default_visibility', Visibility::class),
            showUnsafeContent: $request->boolean('show_unsafe_content'),
            showUploads: $request->boolean('show_uploads'),
            touchesDisplayName: $request->has('display_name'),
            touchesEmail: $request->has('email'),
            touchesLocale: $request->has('locale'),
            touchesDefaultVisibility: $request->has('default_visibility'),
            touchesShowUnsafeContent: $request->has('show_unsafe_content'),
            touchesShowUploads: $request->has('show_uploads'),
        );

        return redirect()->back(fallback: route('settings.account'))->with('success', __('user::account.saved'));
    }
}
