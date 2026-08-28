<?php

declare(strict_types=1);

namespace Modules\Tag\Services;

use Illuminate\Support\Facades\DB;
use Modules\Media\Models\Media;
use Modules\Tag\Enums\TagSource;

/**
 * Keeps the two denormalized counters honest. Both count `human` rows only:
 * implied tags must not inflate what autocomplete orders by, and `tag_count`
 * answers "how many tags did someone actually put on this".
 */
final class TagUsageCounter
{
    /**
     * @param  list<int>  $tagIds
     */
    public function recalculate(array $tagIds): void
    {
        foreach (array_unique($tagIds) as $tagId) {
            $count = DB::table('media_tag')
                ->where('tag_id', $tagId)
                ->where('source', TagSource::Human->value)
                ->count();

            DB::table('tags')->where('id', $tagId)->update(['usage_count' => $count]);
        }
    }

    /**
     * Whole-table recomputation, for the rebuild command. Two statements
     * rather than a correlated update, which is not portable.
     */
    public function recalculateAll(): void
    {
        DB::table('tags')->update(['usage_count' => 0]);

        $counts = DB::table('media_tag')
            ->select('tag_id', DB::raw('COUNT(*) as aggregate'))
            ->where('source', TagSource::Human->value)
            ->groupBy('tag_id')
            ->get();

        foreach ($counts as $row) {
            DB::table('tags')->where('id', $row->tag_id)->update(['usage_count' => (int) $row->aggregate]);
        }
    }

    public function refreshMediaTagCount(Media $media): void
    {
        $count = DB::table('media_tag')
            ->where('media_id', $media->id)
            ->where('source', TagSource::Human->value)
            ->count();

        DB::table('media')->where('id', $media->id)->update(['tag_count' => $count]);
    }
}
