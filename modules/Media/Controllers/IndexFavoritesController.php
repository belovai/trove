<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Models\Media;
use Modules\Media\Requests\BrowseMediaRequest;
use Modules\Media\Support\MediaCardPayload;

final class IndexFavoritesController
{
    public function __invoke(BrowseMediaRequest $request): Response
    {
        $viewer = $request->user();

        $filters = $request->filters();

        $media = Media::query()->visibleTo($viewer)
            ->favoritedBy($viewer)
            ->withinSafetyFilter($viewer, $filters->ratings)
            ->tap(fn (Builder $query) => $filters->sort->apply($query))
            ->paginate(60)
            ->withQueryString()
            ->through(fn (Media $item): array => MediaCardPayload::for($item));

        return Inertia::render('favorites/Index', [
            'media' => $media,
            'filters' => $filters->toArray(),
        ]);
    }
}
