<?php

use App\Providers\AppServiceProvider;
use Modules\Auth\Providers\AuthModuleServiceProvider;
use Modules\User\Providers\UserModuleServiceProvider;

return [
    AppServiceProvider::class,
    UserModuleServiceProvider::class,
    AuthModuleServiceProvider::class,
];
