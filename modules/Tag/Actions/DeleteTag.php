<?php

declare(strict_types=1);

namespace Modules\Tag\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Media\Models\Media;
use Modules\Tag\Enums\TagSource;
use Modules\Tag\Models\Tag;

/**
 * A hard delete. Aliases, implications and pivot rows go with it by foreign
 * key cascade; the items that carried it then need their implied set
 * recomputed, because an implication path may have disappeared with the tag.
 */
final class DeleteTag
{
    public function __construct(
        private readonly SyncMediaTags $sync,
    ) {}

    public function handle(Tag $tag): void
    {
        DB::transaction(function () use ($tag): void {
            $affectedMediaIds = DB::table('media_tag')
                ->where('tag_id', $tag->id)
                ->pluck('media_id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $tag->delete();

            foreach (Media::query()->whereIn('id', $affectedMediaIds)->get() as $media) {
                $humanIds = DB::table('media_tag')
                    ->where('media_id', $media->id)
                    ->where('source', TagSource::Human->value)
                    ->pluck('tag_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();

                $this->sync->handle($media, $humanIds, null);
            }
        });
    }
}
