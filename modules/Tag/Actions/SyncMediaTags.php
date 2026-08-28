<?php

declare(strict_types=1);

namespace Modules\Tag\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Media\Models\Media;
use Modules\Tag\Enums\TagSource;
use Modules\Tag\Services\ImplicationClosureResolver;
use Modules\Tag\Services\TagUsageCounter;
use Modules\User\Models\User;

/**
 * The only writer of `media_tag`. Adding and removing are the same operation:
 * the caller states the complete set of human tags, and the implied set is
 * recomputed from scratch.
 *
 * Recomputation rather than targeted deletes is what makes overlapping
 * implication paths correct — when two human tags imply the same tag, removing
 * one of them must not remove the implied tag.
 */
final class SyncMediaTags
{
    public function __construct(
        private readonly ImplicationClosureResolver $resolver,
        private readonly TagUsageCounter $counter,
    ) {}

    /**
     * @param  list<int>  $tagIds  the complete set of human tags for this item
     */
    public function handle(Media $media, array $tagIds, ?User $taggedBy): void
    {
        $tagIds = array_values(array_unique($tagIds));

        DB::transaction(function () use ($media, $tagIds, $taggedBy): void {
            $before = DB::table('media_tag')->where('media_id', $media->id)->pluck('tag_id')->all();

            // whereNotIn with an empty array is `1 = 1`, so clearing every tag
            // is the same code path as changing them.
            DB::table('media_tag')
                ->where('media_id', $media->id)
                ->where('source', TagSource::Human->value)
                ->whereNotIn('tag_id', $tagIds)
                ->delete();

            if ($tagIds !== []) {
                // Upsert, not insertOrIgnore: a tag previously present as
                // `implied` and now typed by hand must be promoted to `human`.
                DB::table('media_tag')->upsert(
                    array_map(static fn (int $tagId): array => [
                        'media_id' => $media->id,
                        'tag_id' => $tagId,
                        'source' => TagSource::Human->value,
                        'tagged_by' => $taggedBy?->id,
                        'created_at' => now(),
                    ], $tagIds),
                    ['media_id', 'tag_id'],
                    // Only `source` is updated. `tagged_by` is written on
                    // insert and never overwritten, so a structural resync
                    // (merge, delete, rebuild) passing a null tagger cannot
                    // erase who originally attached the tag.
                    ['source'],
                );
            }

            $implied = $this->resolver->expand($tagIds);

            DB::table('media_tag')
                ->where('media_id', $media->id)
                ->where('source', TagSource::Implied->value)
                ->whereNotIn('tag_id', $implied)
                ->delete();

            if ($implied !== []) {
                // insertOrIgnore, not upsert: an existing row here is a human
                // row, and a human row is never demoted to implied.
                DB::table('media_tag')->insertOrIgnore(
                    array_map(static fn (int $tagId): array => [
                        'media_id' => $media->id,
                        'tag_id' => $tagId,
                        'source' => TagSource::Implied->value,
                        'tagged_by' => null,
                        'created_at' => now(),
                    ], $implied),
                );
            }

            $this->counter->recalculate([...$before, ...$tagIds, ...$implied]);
            $this->counter->refreshMediaTagCount($media);
        });
    }
}
