<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Models\Media;
use Modules\Media\Requests\BrowseMediaRequest;
use Modules\Media\Support\MediaCardPayload;

final class BrowseMediaController
{
    public function __invoke(BrowseMediaRequest $request): Response
    {
        $viewer = $request->user();

        // Resolved up front rather than left to the scope, because the filter
        // bar has to render the set that actually applied.
        $filters = $request->filters();

        $media = Media::query()->visibleTo($viewer)
            ->when(
                $filters->unlisted,
                fn (Builder $query) => $query->ownUnlisted($viewer),
                fn (Builder $query) => $query->listable(),
            )
            ->withinSafetyFilter($viewer, $filters->ratings)
            ->when($filters->untagged, fn (Builder $query) => $query->untagged())
            ->latest()
            ->paginate(60)
            ->withQueryString()
            ->through(fn (Media $item): array => MediaCardPayload::for($item));

        return Inertia::render('media/Index', [
            'media' => $media,
            'filters' => $filters->toArray(),
        ]);
    }
}
