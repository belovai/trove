<?php

declare(strict_types=1);

namespace Modules\Tag\Actions;

use Modules\Tag\Exceptions\InvalidTaxonomyEdge;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagImplication;
use Modules\Tag\Services\ImplicationClosureResolver;

final class CreateImplication
{
    public function __construct(
        private readonly ImplicationClosureResolver $resolver,
    ) {}

    /**
     * Existing media is not retroactively updated — run
     * `trove:rebuild-implications` for that. Implied rows are never
     * authoritative, so rebuilding is always safe.
     *
     * @throws InvalidTaxonomyEdge
     */
    public function handle(Tag $tag, Tag $implied): TagImplication
    {
        if ($tag->id === $implied->id) {
            throw InvalidTaxonomyEdge::selfImplication($tag->name);
        }

        if ($tag->implications()->where('implied_tag_id', $implied->id)->exists()) {
            throw InvalidTaxonomyEdge::implicationExists($tag->name, $implied->name);
        }

        // If the target already reaches the source, adding this edge closes a
        // loop. Checked before insert, so the graph is acyclic by construction.
        if ($this->resolver->reaches($implied->id, $tag->id)) {
            throw InvalidTaxonomyEdge::implicationCycle($tag->name, $implied->name);
        }

        return TagImplication::query()->create([
            'tag_id' => $tag->id,
            'implied_tag_id' => $implied->id,
        ]);
    }
}
