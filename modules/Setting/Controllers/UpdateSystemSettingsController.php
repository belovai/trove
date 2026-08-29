<?php

declare(strict_types=1);

namespace Modules\Setting\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Setting\Actions\SetSetting;
use Modules\Setting\Requests\UpdateSystemSettingsRequest;

final class UpdateSystemSettingsController
{
    public function __invoke(UpdateSystemSettingsRequest $request, SetSetting $setSetting): RedirectResponse
    {
        $submitted = $request->submitted();

        // Validate every submitted key before writing any of them, so one
        // invalid key can't leave an earlier key's write in place.
        foreach ($submitted as $key => $value) {
            $setSetting->validate($key, $value);
        }

        foreach ($submitted as $key => $value) {
            $setSetting->handle($key, $value);
        }

        return back()->with('success', __('setting::setting.saved'));
    }
}
