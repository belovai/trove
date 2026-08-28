<?php

declare(strict_types=1);

namespace Modules\Media\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\Media\Contracts\MediaStorage;
use Modules\Media\Models\Media;

final class PruneDeletedMedia extends Command
{
    protected $signature = 'media:prune';

    protected $description = 'Permanently remove media soft-deleted longer ago than the retention window.';

    public function handle(MediaStorage $storage): int
    {
        $cutoff = now()->subDays((int) config('trove.media.prune_after_days'));

        Media::query()->onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->chunkById(100, function (Collection $chunk) use ($storage): void {
                foreach ($chunk as $media) {
                    $storage->delete($media->hash_id);
                    $media->forceDelete();

                    $this->line("Pruned {$media->hash_id}");
                }
            });

        return self::SUCCESS;
    }
}
