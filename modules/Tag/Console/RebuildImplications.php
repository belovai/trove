<?php

declare(strict_types=1);

namespace Modules\Tag\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Tag\Enums\TagSource;
use Modules\Tag\Services\ImplicationClosureResolver;
use Modules\Tag\Services\TagUsageCounter;

/**
 * Re-derives every implied row from the human rows. Safe by construction:
 * implied rows are never authoritative, so throwing them all away loses
 * nothing that cannot be recomputed.
 *
 * Run after changing or deleting an implication — those do not retroactively
 * alter existing media.
 */
final class RebuildImplications extends Command
{
    protected $signature = 'trove:rebuild-implications';

    protected $description = 'Delete every implied tag row and re-derive it from the human rows.';

    public function handle(ImplicationClosureResolver $resolver, TagUsageCounter $counter): int
    {
        DB::table('media_tag')->where('source', TagSource::Implied->value)->delete();

        $mediaIds = DB::table('media_tag')
            ->where('source', TagSource::Human->value)
            ->distinct()
            ->pluck('media_id');

        $bar = $this->output->createProgressBar($mediaIds->count());

        foreach ($mediaIds->chunk(200) as $chunk) {
            foreach ($chunk as $mediaId) {
                $humanIds = DB::table('media_tag')
                    ->where('media_id', $mediaId)
                    ->where('source', TagSource::Human->value)
                    ->pluck('tag_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();

                $implied = $resolver->expand($humanIds);

                if ($implied !== []) {
                    // insertOrIgnore: a tag already present as human keeps its
                    // human row.
                    DB::table('media_tag')->insertOrIgnore(
                        array_map(static fn (int $tagId): array => [
                            'media_id' => $mediaId,
                            'tag_id' => $tagId,
                            'source' => TagSource::Implied->value,
                            'tagged_by' => null,
                            'created_at' => now(),
                        ], $implied),
                    );
                }

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();

        $counter->recalculateAll();

        $this->info('Implications rebuilt.');

        return self::SUCCESS;
    }
}
