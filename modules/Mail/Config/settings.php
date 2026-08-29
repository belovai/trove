<?php

declare(strict_types=1);

use Illuminate\Validation\Rule;
use Modules\Mail\Support\MailTransportRegistry;
use Modules\Setting\Support\SettingDefinition;

/*
 * env() supplies the first-boot default only. Once an administrator saves a
 * value the database wins, exactly as with registration.*.
 *
 * mail.enabled defaults to false so a half-configured installation never
 * attempts a real delivery: with it off, MailConfigurator selects Laravel's
 * array transport and nothing leaves the process.
 */

return [
    'mail.enabled' => SettingDefinition::bool(false),

    'mail.transport' => SettingDefinition::string('log')
        ->rules(['required', 'string', Rule::in(MailTransportRegistry::keys())]),

    'mail.from_address' => SettingDefinition::string((string) env('MAIL_FROM_ADDRESS', ''))
        ->rules(['nullable', 'email', 'max:255']),

    'mail.from_name' => SettingDefinition::string('')
        ->rules(['nullable', 'string', 'max:60']),

    'mail.reply_to' => SettingDefinition::string('')
        ->rules(['nullable', 'email', 'max:255']),

    'mail.admin_address' => SettingDefinition::string('')
        ->rules(['nullable', 'email', 'max:255']),

    ...MailTransportRegistry::definitions(),
];
