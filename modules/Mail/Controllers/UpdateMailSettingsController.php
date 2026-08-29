<?php

declare(strict_types=1);

namespace Modules\Mail\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Mail\Requests\UpdateMailSettingsRequest;
use Modules\Mail\Support\MailConfigurator;
use Modules\Setting\Actions\SetSetting;

final class UpdateMailSettingsController
{
    public function __invoke(
        UpdateMailSettingsRequest $request,
        SetSetting $setSetting,
        MailConfigurator $configurator,
    ): RedirectResponse {
        $submitted = $request->submitted();

        // Validate everything before writing anything, so one invalid key
        // cannot leave an earlier key's write in place.
        foreach ($submitted as $key => $value) {
            $setSetting->validate($key, $value);
        }

        foreach ($submitted as $key => $value) {
            $setSetting->handle($key, $value);
        }

        // The test send that usually follows must use what was just saved.
        $configurator->apply();

        return back()->with('success', __('mail::mail.saved'));
    }
}
