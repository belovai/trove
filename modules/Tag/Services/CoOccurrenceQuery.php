<?php

declare(strict_types=1);

namespace Modules\Tag\Services;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Modules\Tag\DataObjects\RelatedTag;
use Modules\Tag\Models\Tag;

/**
 * Related tags, derived from the pivot rather than hand-maintained. This is
 * what the reference implementation's manual "suggestions" list is replaced
 * with: no maintenance, and better data.
 */
final class CoOccurrenceQuery
{
    /** Below this many shared items a pair is noise, not a relationship. */
    public const MINIMUM_SUPPORT = 2;

    /**
     * @return list<RelatedTag>
     */
    public function relatedTo(Tag $tag, int $limit = 12): array
    {
        $rows = DB::table('media_tag as mine')
            ->join('media_tag as other', function (JoinClause $join): void {
                $join->on('other.media_id', '=', 'mine.media_id')
                    ->whereColumn('other.tag_id', '!=', 'mine.tag_id');
            })
            ->join('tags', 'tags.id', '=', 'other.tag_id')
            ->leftJoin('tag_categories', 'tag_categories.id', '=', 'tags.category_id')
            ->where('mine.tag_id', $tag->id)
            ->groupBy('other.tag_id', 'tags.name', 'tag_categories.name', 'tag_categories.color')
            ->havingRaw('COUNT(*) >= ?', [self::MINIMUM_SUPPORT])
            ->select([
                'other.tag_id',
                'tags.name',
                'tag_categories.name as category',
                'tag_categories.color as color',
                DB::raw('COUNT(*) as shared'),
                // Correlated subquery rather than tags.usage_count: that
                // column counts human rows only, while `shared` counts every
                // row. Comparing the two would skew the score.
                DB::raw('(SELECT COUNT(*) FROM media_tag mt WHERE mt.tag_id = other.tag_id) as total'),
            ])
            ->get();

        $related = $rows->map(static function (object $row): RelatedTag {
            $total = max((int) $row->total, 1);

            return new RelatedTag(
                tagId: (int) $row->tag_id,
                name: (string) $row->name,
                category: $row->category === null ? null : (string) $row->category,
                color: $row->color === null ? null : (string) $row->color,
                shared: (int) $row->shared,
                // Normalising by the other tag's own frequency is what keeps a
                // huge tag like "meme" from being the top companion of
                // everything.
                score: (int) $row->shared / $total,
            );
        })->all();

        usort(
            $related,
            static fn (RelatedTag $a, RelatedTag $b): int => [$b->score, $b->shared] <=> [$a->score, $a->shared],
        );

        return array_slice($related, 0, $limit);
    }
}
