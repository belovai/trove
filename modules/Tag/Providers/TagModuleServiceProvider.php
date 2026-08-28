<?php

declare(strict_types=1);

namespace Modules\Tag\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Tag\Console\RebuildImplications;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;
use Modules\Tag\Policies\TagCategoryPolicy;
use Modules\Tag\Policies\TagPolicy;

final class TagModuleServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(TagCategory::class, TagCategoryPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([RebuildImplications::class]);
        }
    }
}
