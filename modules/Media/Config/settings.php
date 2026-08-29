<?php

declare(strict_types=1);

use Modules\Media\Enums\Visibility;
use Modules\Setting\Support\SettingDefinition;

return [
    'media.default_visibility' => SettingDefinition::enum(Visibility::class, Visibility::Public->value),
];
