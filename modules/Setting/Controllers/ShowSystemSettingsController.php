<?php

declare(strict_types=1);

namespace Modules\Setting\Controllers;

use App\Support\SettingsSections;
use BackedEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Auth\Enums\EmailVerificationMode;
use Modules\Auth\Enums\RegistrationEmailPolicy;
use Modules\Auth\Enums\RegistrationMode;
use Modules\Media\Enums\Visibility;
use Modules\Setting\Facades\Settings;
use Modules\Setting\Requests\UpdateSystemSettingsRequest;

final class ShowSystemSettingsController
{
    public function __invoke(Request $request): Response
    {
        $values = [];

        foreach (UpdateSystemSettingsRequest::KEYS as $key) {
            $value = Settings::get($key);
            // Enums cross the wire as their backing value; the client never
            // reconstructs a PHP type.
            $values[$key] = $value instanceof BackedEnum ? $value->value : $value;
        }

        return Inertia::render('settings/System', [
            'sections' => SettingsSections::for($request->user()),
            'current' => 'system',
            'settings' => $values,
            'registration_modes' => array_column(RegistrationMode::cases(), 'value'),
            'email_policies' => array_column(RegistrationEmailPolicy::cases(), 'value'),
            'verification_modes' => array_column(EmailVerificationMode::cases(), 'value'),
            'visibilities' => array_column(Visibility::cases(), 'value'),
        ]);
    }
}
