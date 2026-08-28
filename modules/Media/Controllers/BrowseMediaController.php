<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Models\Media;
use Modules\Media\Requests\BrowseMediaRequest;

final class BrowseMediaController
{
    public function __invoke(BrowseMediaRequest $request): Response
    {
        $viewer = $request->user();

        // Resolved up front rather than left to the scope, because the filter
        // bar has to render the set that actually applied.
        $filters = $request->filters();

        $media = Media::query()->visibleTo($viewer)
            ->listable()
            ->withinSafetyFilter($viewer, $filters->ratings)
            ->when($filters->untagged, fn (Builder $query) => $query->untagged())
            ->latest()
            ->paginate(60)
            ->withQueryString()
            ->through(fn (Media $item): array => $this->card($item));

        return Inertia::render('media/Index', [
            'media' => $media,
            'filters' => $filters->toArray(),
        ]);
    }

    /**
     * The public shape of a grid item. The internal id, the storage path and
     * an anonymous uploader's identity never leave the server.
     *
     * @return array<string, mixed>
     */
    private function card(Media $item): array
    {
        return [
            'hash_id' => $item->hash_id,
            'title' => $item->title,
            'width' => $item->width,
            'height' => $item->height,
            'dominant_color' => $item->dominant_color,
            'safety_rating' => $item->safety_rating->value,
            'has_thumbnail' => $item->thumbnails !== null,
            'tag_count' => $item->tag_count,
        ];
    }
}
