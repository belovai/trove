<?php

declare(strict_types=1);

namespace Modules\User\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\User\Models\User;

final class UserModuleServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // A banned account is denied every ability, whatever its rank.
        // Returning null lets the normal gates decide for everyone else.
        Gate::before(fn (User $user): ?bool => $user->isBanned() ? false : null);
    }
}
