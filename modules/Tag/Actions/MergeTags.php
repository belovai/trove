<?php

declare(strict_types=1);

namespace Modules\Tag\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Media\Models\Media;
use Modules\Tag\Enums\TagSource;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagAlias;
use Modules\Tag\Models\TagImplication;
use Modules\Tag\Services\TagUsageCounter;

/**
 * Merges $source into $target and deletes $source. The source's name survives
 * as an alias, so anyone still typing it lands on the target.
 */
final class MergeTags
{
    public function __construct(
        private readonly SyncMediaTags $sync,
        private readonly TagUsageCounter $counter,
    ) {}

    public function handle(Tag $source, Tag $target): Tag
    {
        if ($source->id === $target->id) {
            return $target;
        }

        DB::transaction(function () use ($source, $target): void {
            $affectedMediaIds = DB::table('media_tag')
                ->where('tag_id', $source->id)
                ->pluck('media_id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $this->movePivotRows($source, $target);
            $this->movePointers($source, $target);
            $this->moveImplications($source, $target);

            $sourceName = $source->name;
            $source->delete();

            TagAlias::query()->create(['alias_name' => $sourceName, 'tag_id' => $target->id]);

            // The target may now imply tags the item does not carry yet, and
            // an item that held both tags is one human tag lighter. Resyncing
            // from the remaining human rows settles both.
            $this->resync($affectedMediaIds);

            $this->counter->recalculate([$target->id]);
        });

        return $target->refresh();
    }

    /**
     * Rows on items that already carry the target collapse into it: a human
     * source row promotes the target's row to human, then the source row goes.
     * The rest are simply repointed.
     */
    private function movePivotRows(Tag $source, Tag $target): void
    {
        $humanMediaIds = DB::table('media_tag')
            ->where('tag_id', $source->id)
            ->where('source', TagSource::Human->value)
            ->pluck('media_id');

        DB::table('media_tag')
            ->where('tag_id', $target->id)
            ->whereIn('media_id', $humanMediaIds)
            ->update(['source' => TagSource::Human->value]);

        $collidingMediaIds = DB::table('media_tag')
            ->where('tag_id', $target->id)
            ->pluck('media_id');

        DB::table('media_tag')
            ->where('tag_id', $source->id)
            ->whereIn('media_id', $collidingMediaIds)
            ->delete();

        DB::table('media_tag')
            ->where('tag_id', $source->id)
            ->update(['tag_id' => $target->id]);
    }

    private function movePointers(Tag $source, Tag $target): void
    {
        TagAlias::query()->where('tag_id', $source->id)->update(['tag_id' => $target->id]);
    }

    /**
     * Edges are copied rather than repointed, because the target may already
     * have the same edge. Self-edges that would result from the merge are
     * dropped: a tag never implies itself.
     */
    private function moveImplications(Tag $source, Tag $target): void
    {
        foreach (TagImplication::query()->where('tag_id', $source->id)->get() as $edge) {
            if ($edge->implied_tag_id === $target->id) {
                continue;
            }

            TagImplication::query()->firstOrCreate([
                'tag_id' => $target->id,
                'implied_tag_id' => $edge->implied_tag_id,
            ]);
        }

        foreach (TagImplication::query()->where('implied_tag_id', $source->id)->get() as $edge) {
            if ($edge->tag_id === $target->id) {
                continue;
            }

            TagImplication::query()->firstOrCreate([
                'tag_id' => $edge->tag_id,
                'implied_tag_id' => $target->id,
            ]);
        }
    }

    /**
     * @param  list<int>  $mediaIds
     */
    private function resync(array $mediaIds): void
    {
        foreach (Media::query()->whereIn('id', $mediaIds)->get() as $media) {
            $humanIds = DB::table('media_tag')
                ->where('media_id', $media->id)
                ->where('source', TagSource::Human->value)
                ->pluck('tag_id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $this->sync->handle($media, $humanIds, null);
        }
    }
}
