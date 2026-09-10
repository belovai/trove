<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use Illuminate\Support\Facades\DB;
use Modules\Media\Models\Media;

/**
 * Keeps the denormalized media.score honest. The only writer of that column.
 *
 * Recalculation from SUM, never an increment: recalculating is idempotent, so
 * a retried or interleaved write cannot leave the column drifting from the
 * rows it summarizes. Same reasoning as the Tag module's TagUsageCounter.
 *
 * Soft-deleted items are counted normally — their votes are kept so a restore
 * is lossless, and a deleted item is not listable anyway.
 */
final class VoteScoreCounter
{
    public function recalculate(Media $media): int
    {
        $score = (int) DB::table('votes')->where('media_id', $media->id)->sum('value');

        DB::table('media')->where('id', $media->id)->update(['score' => $score]);

        // The caller usually holds the model it just changed. Syncing the
        // original too, so the refreshed value does not read as an unsaved edit.
        $media->setAttribute('score', $score);
        $media->syncOriginalAttribute('score');

        return $score;
    }
}
