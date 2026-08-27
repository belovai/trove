<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Modules\Media\Models\Media;

final class DeleteMedia
{
    /**
     * Soft delete: the row stays, so the hash id is never reissued and the
     * file remains for a moderator to review. PruneDeletedMedia removes both
     * once the retention window has passed.
     */
    public function handle(Media $media): void
    {
        $media->delete();
    }
}
