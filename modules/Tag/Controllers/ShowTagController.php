<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\DataObjects\BrowseFilters;
use Modules\Media\Models\Media;
use Modules\Media\Requests\BrowseMediaRequest;
use Modules\Tag\DataObjects\RelatedTag;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;
use Modules\Tag\Services\CoOccurrenceQuery;
use Modules\Tag\Services\ImplicationClosureResolver;

final class ShowTagController
{
    private const SAMPLE_SIZE = 30;

    public function __construct(
        private readonly ImplicationClosureResolver $resolver,
        private readonly CoOccurrenceQuery $coOccurrence,
    ) {}

    public function __invoke(BrowseMediaRequest $request, string $tag): Response
    {
        $model = Tag::query()->with(['category', 'aliases'])->where('name', $tag)->firstOrFail();

        // The tag page is an entry point into browsing, so it carries the same
        // safety filter bar. "Untagged only" is meaningless here — every item
        // in the sample carries this tag — so the bar hides that toggle.
        $filters = $request->filters();

        return Inertia::render('tags/Show', [
            'tag' => [
                'name' => $model->name,
                'description' => $model->description,
                'category' => $model->category?->name,
                'color' => $model->category?->color,
                'usage_count' => $model->usage_count,
                'aliases' => $model->aliases->pluck('alias_name')->values(),
                // The tree: what leads here, and what this leads to.
                'ancestors' => $this->names($this->resolver->ancestors($model->id)),
                'descendants' => $this->names($this->resolver->expand([$model->id])),
            ],
            'related' => array_map(static fn (RelatedTag $related): array => [
                'name' => $related->name,
                'category' => $related->category,
                'color' => $related->color,
                'shared' => $related->shared,
            ], $this->coOccurrence->relatedTo($model)),
            'media' => $this->sampleMedia($request, $model, $filters),
            'filters' => $filters->toArray(),
            'categories' => $request->user()?->can('tag.manage')
                ? TagCategory::query()->orderBy('sort_order')->orderBy('name')->get()
                    ->map(static fn (TagCategory $category): array => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ])->all()
                : [],
            'can' => [
                'manage' => $request->user()?->can('tag.manage') ?? false,
            ],
        ]);
    }

    /**
     * @param  list<int>  $ids
     * @return list<string>
     */
    private function names(array $ids): array
    {
        return Tag::query()->whereIn('id', $ids)->orderBy('name')->pluck('name')->all();
    }

    /**
     * Through the media visibility chokepoint, never around it. The tag page
     * is an entry point into browsing, so listable() and the safety filter
     * apply exactly as they do in browse.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sampleMedia(Request $request, Tag $tag, BrowseFilters $filters): array
    {
        return Media::query()->visibleTo($request->user())
            ->listable()
            ->withinSafetyFilter($request->user(), $filters->ratings)
            ->whereIn('id', fn (QueryBuilder $query) => $query
                ->select('media_id')
                ->from('media_tag')
                ->where('tag_id', $tag->id))
            ->orderByDesc('created_at')
            ->limit(self::SAMPLE_SIZE)
            ->get()
            ->map(fn (Media $item): array => [
                'hash_id' => $item->hash_id,
                'title' => $item->title,
                'width' => $item->width,
                'height' => $item->height,
                'dominant_color' => $item->dominant_color,
                'safety_rating' => $item->safety_rating->value,
                'has_thumbnail' => $item->thumbnails !== null,
                'tag_count' => $item->tag_count,
            ])
            ->all();
    }
}
