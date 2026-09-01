<?php

declare(strict_types=1);

namespace Modules\User\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\User\Console\CreateUserCommand;
use Modules\User\Contracts\AvatarStorage;
use Modules\User\Models\User;
use Modules\User\Policies\UserPolicy;
use Modules\User\Services\LocalAvatarStorage;

final class UserModuleServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            AvatarStorage::class,
            fn (): LocalAvatarStorage => new LocalAvatarStorage(config('trove.avatar.disk')),
        );
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(User::class, UserPolicy::class);

        // A banned account is denied every ability, whatever its rank.
        // Returning null lets the normal gates decide for everyone else.
        Gate::before(fn (User $user): ?bool => $user->isBanned() ? false : null);

        if ($this->app->runningInConsole()) {
            $this->commands([CreateUserCommand::class]);
        }
    }
}
