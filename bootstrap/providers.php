<?php

use App\Providers\AppServiceProvider;
use Modules\Auth\Providers\AuthModuleServiceProvider;
use Modules\Media\Providers\MediaModuleServiceProvider;
use Modules\Tag\Providers\TagModuleServiceProvider;
use Modules\User\Providers\UserModuleServiceProvider;

return [
    AppServiceProvider::class,
    UserModuleServiceProvider::class,
    AuthModuleServiceProvider::class,
    MediaModuleServiceProvider::class,
    TagModuleServiceProvider::class,
];
