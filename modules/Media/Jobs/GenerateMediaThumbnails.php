<?php

declare(strict_types=1);

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Media\Contracts\MediaStorage;
use Modules\Media\Contracts\ThumbnailGenerator;
use Modules\Media\Enums\ThumbnailSize;
use Modules\Media\Models\Media;

/**
 * Dispatched at the end of the upload. With QUEUE_CONNECTION=sync it runs
 * inline; moving to a worker is a config change, not a code change.
 */
final class GenerateMediaThumbnails implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Media $media,
    ) {}

    public function handle(MediaStorage $storage, ThumbnailGenerator $generator): void
    {
        if (!$generator->supports($this->media->mime_type)) {
            return;
        }

        $thumbnails = [];

        foreach (ThumbnailSize::cases() as $size) {
            $thumbnails[$size->value] = $generator->generate($this->media, $size);
        }

        // A failure here leaves thumbnails null, which the UI renders as the
        // dominant_color placeholder. The job can then simply be re-run.
        $this->media->forceFill(['thumbnails' => $thumbnails])->save();
    }
}
