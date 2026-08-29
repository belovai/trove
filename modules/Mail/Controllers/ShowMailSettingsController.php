<?php

declare(strict_types=1);

namespace Modules\Mail\Controllers;

use App\Contracts\SettingRegistry;
use App\Support\SettingsSections;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Mail\Support\MailConfigurator;
use Modules\Mail\Support\MailTransportRegistry;
use Modules\Setting\Facades\Settings;

final class ShowMailSettingsController
{
    public function __invoke(
        Request $request,
        SettingRegistry $registry,
        MailConfigurator $configurator,
    ): Response {
        $values = [];
        $secrets = [];

        foreach (array_keys($registry->namespace('mail')) as $key) {
            $definition = $registry->get($key);

            // A secret's value never leaves the server; the form only learns
            // whether one is stored.
            if ($definition->isEncrypted) {
                $secrets[$key] = ((string) Settings::get($key)) !== '';
                $values[$key] = '';

                continue;
            }

            $values[$key] = Settings::get($key);
        }

        return Inertia::render('settings/Mail', [
            'sections' => SettingsSections::for($request->user()),
            'current' => 'mail',
            'settings' => $values,
            'secrets' => $secrets,
            'transports' => MailTransportRegistry::keys(),
            'transport_labels' => MailTransportRegistry::labels(),
            'fields' => MailTransportRegistry::fields(),
            'deliverable' => $configurator->isDeliverable(),
        ]);
    }
}
