<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Media\Models\Media;
use Modules\Tag\Services\TagUsageCounter;

final class DeleteMedia
{
    public function __construct(private readonly TagUsageCounter $counter) {}

    /**
     * Soft delete: the row stays, so the hash id is never reissued and the
     * file remains for a moderator to review. PruneDeletedMedia removes both
     * once the retention window has passed.
     *
     * The pivot rows stay too — a restore must not lose the tags — so the tag
     * counters are recomputed instead, or they would keep promising an item
     * the tag page can no longer list.
     */
    public function handle(Media $media): void
    {
        DB::transaction(function () use ($media): void {
            $media->delete();

            $this->counter->recalculateForMedia($media);
        });
    }
}
