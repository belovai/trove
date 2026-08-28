<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Models\Media;

final class BrowseMediaController
{
    public function __invoke(Request $request): Response
    {
        $viewer = $request->user();

        $media = Media::query()->visibleTo($viewer)
            ->listable()
            ->withinSafetyFilter($viewer)
            ->latest()
            ->paginate(60)
            ->through(fn (Media $item): array => $this->card($item));

        return Inertia::render('media/Index', ['media' => $media]);
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
