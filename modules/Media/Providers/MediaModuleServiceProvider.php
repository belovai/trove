<?php

declare(strict_types=1);

namespace Modules\Media\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Modules\Media\Console\PruneDeletedMedia;
use Modules\Media\Contracts\MediaStorage;
use Modules\Media\Contracts\MetadataExtractor;
use Modules\Media\Contracts\ThumbnailGenerator;
use Modules\Media\Models\Media;
use Modules\Media\Policies\MediaPolicy;
use Modules\Media\Services\ImageMetadataExtractor;
use Modules\Media\Services\InterventionThumbnailGenerator;
use Modules\Media\Services\LocalMediaStorage;

final class MediaModuleServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            MediaStorage::class,
            fn (): LocalMediaStorage => new LocalMediaStorage(config('trove.media.disk')),
        );

        $this->app->singleton(ImageManager::class, fn (): ImageManager => match (config('trove.media.image_driver')) {
            'gd' => new ImageManager(new GdDriver),
            default => new ImageManager(new ImagickDriver),
        });

        $this->app->bind(MetadataExtractor::class, ImageMetadataExtractor::class);

        $this->app->bind(ThumbnailGenerator::class, InterventionThumbnailGenerator::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Media::class, MediaPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([PruneDeletedMedia::class]);
        }
    }
}
