<?php

declare(strict_types=1);

namespace Modules\Tag\Services;

use Illuminate\Support\Collection;
use Modules\Tag\DataObjects\DuplicateCandidate;
use Modules\Tag\DataObjects\ImplicationCandidate;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagImplication;

/**
 * Surfaces taxonomy problems without enforcing anything. Permissive tagging is
 * the policy: this report is the starting point for a human decision, never an
 * automatic action.
 */
final class TagHealthReport
{
    /** Names within this edit distance are worth a human glance. */
    private const MAX_EDIT_DISTANCE = 1;

    /** Below this, a near-duplicate check on short names is all false positives. */
    private const MINIMUM_NAME_LENGTH = 4;

    /** How often A must accompany B before an implication is worth suggesting. */
    private const IMPLICATION_CONFIDENCE = 0.9;

    /** A pair needs at least this many shared items before confidence means anything. */
    private const IMPLICATION_SUPPORT = 5;

    public function __construct(
        private readonly CoOccurrenceQuery $coOccurrence,
    ) {}

    /**
     * @return Collection<int, Tag>
     */
    public function unused(): Collection
    {
        return Tag::query()->where('usage_count', 0)->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Tag>
     */
    public function uncategorized(): Collection
    {
        return Tag::query()->whereNull('category_id')->orderBy('name')->get();
    }

    /**
     * Edit distance is computed in PHP, not SQL: no portable driver has it.
     * Names are bucketed by length so the comparison is not quadratic over the
     * whole table.
     *
     * PHP's levenshtein() is byte-based, so for non-ASCII names this is an
     * approximation. It is a hint for a human, so that is acceptable.
     *
     * @return list<DuplicateCandidate>
     */
    public function nearDuplicates(): array
    {
        $tags = Tag::query()->orderBy('name')->get(['id', 'name'])
            ->filter(static fn (Tag $tag): bool => mb_strlen($tag->name) >= self::MINIMUM_NAME_LENGTH)
            ->values();

        $candidates = [];

        foreach ($tags as $index => $left) {
            foreach ($tags->slice($index + 1) as $right) {
                if (abs(strlen($left->name) - strlen($right->name)) > self::MAX_EDIT_DISTANCE) {
                    continue;
                }

                $distance = levenshtein($left->name, $right->name);

                if ($distance > 0 && $distance <= self::MAX_EDIT_DISTANCE) {
                    $candidates[] = new DuplicateCandidate(
                        leftId: $left->id,
                        left: $left->name,
                        rightId: $right->id,
                        right: $right->name,
                        distance: $distance,
                    );
                }
            }
        }

        return $candidates;
    }

    /**
     * Pairs where nearly every item carrying A also carries B. Existing
     * implications are excluded, so accepting a candidate removes it from the
     * next report.
     *
     * @return list<ImplicationCandidate>
     */
    public function implicationCandidates(): array
    {
        $existing = TagImplication::query()->get()
            ->map(static fn (TagImplication $edge): string => "{$edge->tag_id}:{$edge->implied_tag_id}")
            ->all();

        $candidates = [];

        foreach (Tag::query()->where('usage_count', '>=', self::IMPLICATION_SUPPORT)->get() as $tag) {
            foreach ($this->coOccurrence->relatedTo($tag, 50) as $related) {
                $confidence = $related->shared / max($tag->usage_count, 1);

                if ($confidence < self::IMPLICATION_CONFIDENCE) {
                    continue;
                }

                if (in_array("{$tag->id}:{$related->tagId}", $existing, true)) {
                    continue;
                }

                $candidates[] = new ImplicationCandidate(
                    fromId: $tag->id,
                    fromName: $tag->name,
                    toId: $related->tagId,
                    toName: $related->name,
                    confidence: $confidence,
                );
            }
        }

        usort(
            $candidates,
            static fn (ImplicationCandidate $a, ImplicationCandidate $b): int => $b->confidence <=> $a->confidence,
        );

        return $candidates;
    }
}
