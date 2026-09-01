<?php

declare(strict_types=1);

namespace Modules\Tag\Services;

use Illuminate\Support\Facades\DB;

/**
 * The single owner of every recursive query in this module. Implications are
 * a directed graph over `tag_implications`; this walks it in both directions.
 *
 * Recursive CTEs are the one driver-specific-looking construct the
 * architecture permits: SQLite (>= 3.8.3) and PostgreSQL both support them
 * with identical syntax.
 */
final class ImplicationClosureResolver
{
    /**
     * Defensive bound. Cycles are prevented at insert time and UNION already
     * terminates the walk, so reaching this means the taxonomy is pathological.
     */
    public const MAX_DEPTH = 10;

    /**
     * The transitive closure of everything the given tags imply, excluding the
     * given tags themselves — a tag does not imply itself, and the caller
     * already holds its own ids.
     *
     * @param  list<int>  $tagIds
     * @return list<int>
     */
    public function expand(array $tagIds): array
    {
        return $this->walk($tagIds, 'tag_id', 'implied_tag_id');
    }

    /**
     * The tags that transitively imply this one. Drives the upper half of the
     * implication tree on the tag page.
     *
     * @return list<int>
     */
    public function ancestors(int $tagId): array
    {
        return $this->walk([$tagId], 'implied_tag_id', 'tag_id');
    }

    /**
     * Whether $fromTagId reaches $toTagId by following implications. Cycle
     * prevention asks this before inserting an edge.
     */
    public function reaches(int $fromTagId, int $toTagId): bool
    {
        return in_array($toTagId, $this->expand([$fromTagId]), true);
    }

    /**
     * One recursive walk, parameterised by direction. $from is the column
     * matched against the frontier, $to the column the frontier advances to.
     *
     * UNION rather than UNION ALL: it deduplicates, which both keeps the
     * result set small on diamond-shaped taxonomies and makes a cycle
     * terminate instead of looping forever.
     *
     * Excludes seeds from the result only if they are not reachable from the
     * other seeds. A seed is excluded if its only path into the closure is
     * through itself (e.g., a self-cycle), but is kept if it's legitimately
     * implied by another seed.
     *
     * @param  list<int>  $tagIds
     * @return list<int>
     */
    private function walk(array $tagIds, string $from, string $to): array
    {
        if ($tagIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($tagIds), '?'));

        $sql = <<<SQL
            WITH RECURSIVE closure(tag_id, depth) AS (
                SELECT {$to}, 1
                FROM tag_implications
                WHERE {$from} IN ({$placeholders})

                UNION

                SELECT ti.{$to}, c.depth + 1
                FROM tag_implications ti
                INNER JOIN closure c ON ti.{$from} = c.tag_id
                WHERE c.depth < ?
            )
            SELECT DISTINCT tag_id FROM closure
        SQL;

        /** @var list<object{tag_id: int|string}> $rows */
        $rows = DB::select($sql, [...$tagIds, self::MAX_DEPTH]);

        $ids = array_map(static fn (object $row): int => (int) $row->tag_id, $rows);

        // Seeds that appear in the closure may need exclusion. A seed is
        // excluded only if it's not reachable from the other seeds—i.e., its
        // only path into the closure is through itself.
        $seedsInClosure = array_intersect($ids, $tagIds);

        $idsToExclude = [];
        foreach ($seedsInClosure as $candidateSeed) {
            // Check if this seed is reachable from the other seeds.
            $otherSeeds = array_diff($tagIds, [$candidateSeed]);

            if ($otherSeeds === []) {
                // No other seeds to reach from; exclude this one if it's in the
                // closure (meaning it only got there via self-cycle).
                $idsToExclude[] = $candidateSeed;
            } else {
                // Check if candidateSeed is reachable from otherSeeds.
                $closure = $this->walkRaw(array_values($otherSeeds), $from, $to);

                if (!in_array($candidateSeed, $closure, true)) {
                    // Not reachable from other seeds; exclude it.
                    $idsToExclude[] = $candidateSeed;
                }
            }
        }

        return array_values(array_diff($ids, $idsToExclude));
    }

    /**
     * Internal helper: raw walk without seed exclusion logic. Used by walk()
     * to check reachability when filtering seeds.
     *
     * @param  list<int>  $tagIds
     * @return list<int>
     */
    private function walkRaw(array $tagIds, string $from, string $to): array
    {
        if ($tagIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($tagIds), '?'));

        $sql = <<<SQL
            WITH RECURSIVE closure(tag_id, depth) AS (
                SELECT {$to}, 1
                FROM tag_implications
                WHERE {$from} IN ({$placeholders})

                UNION

                SELECT ti.{$to}, c.depth + 1
                FROM tag_implications ti
                INNER JOIN closure c ON ti.{$from} = c.tag_id
                WHERE c.depth < ?
            )
            SELECT DISTINCT tag_id FROM closure
        SQL;

        /** @var list<object{tag_id: int|string}> $rows */
        $rows = DB::select($sql, [...$tagIds, self::MAX_DEPTH]);

        return array_map(static fn (object $row): int => (int) $row->tag_id, $rows);
    }
}
