<?php

declare(strict_types=1);

namespace Modules\User\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\User\Actions\UpdateAvatar;
use Modules\User\Enums\AvatarSource;
use Modules\User\Models\User;
use Modules\User\Requests\UpdateAvatarRequest;

final class UpdateAvatarController
{
    public function __construct(
        private readonly UpdateAvatar $updateAvatar,
    ) {}

    public function __invoke(UpdateAvatarRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->updateAvatar->handle(
            user: $user,
            source: AvatarSource::from($request->string('source')->toString()),
            file: $request->file('avatar'),
        );

        return redirect()->back(fallback: route('settings.profile'))->with('success', __('user::account.saved'));
    }
}
