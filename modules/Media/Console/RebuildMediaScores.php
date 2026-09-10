<?php

declare(strict_types=1);

namespace Modules\Media\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\Media\Models\Media;
use Modules\Media\Services\VoteScoreCounter;

/**
 * Re-derives every media.score from the votes table. The counter is written to
 * make drift impossible, so this exists for the cases outside its reach: a
 * restored backup, a manual SQL edit, a bug fixed after the fact.
 *
 * Soft-deleted items are included — their votes are kept, so their score
 * should be right when they come back.
 */
final class RebuildMediaScores extends Command
{
    protected $signature = 'media:rebuild-scores';

    protected $description = 'Recalculate the denormalized vote score on every media item.';

    public function handle(VoteScoreCounter $counter): int
    {
        Media::query()->withTrashed()->chunkById(100, function (Collection $chunk) use ($counter): void {
            foreach ($chunk as $media) {
                $counter->recalculate($media);
            }
        });

        return self::SUCCESS;
    }
}
