<?php

declare(strict_types=1);

use Modules\Setting\Support\SettingDefinition;

return [
    'app.name' => SettingDefinition::string((string) env('APP_NAME', 'Trove'))
        ->rules(['required', 'string', 'max:60']),
];
