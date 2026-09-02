<?php

declare(strict_types=1);

use Modules\Setting\Support\SettingDefinition;
use Modules\User\Enums\DateFormat;
use Modules\User\Enums\TimeFormat;

return [
    'app.name' => SettingDefinition::string((string) env('APP_NAME', 'Trove'))
        ->rules(['required', 'string', 'max:60']),

    /*
     * The site-wide date and time presentation. Timestamps are always stored in
     * UTC; these decide how they are rendered for a viewer who has not set a
     * preference of their own (including every logged-out visitor).
     */
    'app.timezone' => SettingDefinition::string((string) env('TROVE_TIMEZONE', 'UTC'))
        ->rules(['required', 'string', 'timezone']),

    'app.date_format' => SettingDefinition::enum(
        DateFormat::class,
        (string) env('TROVE_DATE_FORMAT', DateFormat::Iso->value),
    ),

    'app.time_format' => SettingDefinition::enum(
        TimeFormat::class,
        (string) env('TROVE_TIME_FORMAT', TimeFormat::TwentyFour->value),
    ),
];
