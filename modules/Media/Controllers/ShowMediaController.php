<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Models\Media;
use Modules\Tag\Models\Tag;

final class ShowMediaController
{
    public function __invoke(Request $request, string $media): Response
    {
        // visibleTo() only: an unlisted item is reachable here by its link,
        // even though listable() keeps it out of browse.
        $item = Media::query()->visibleTo($request->user())
            ->where('hash_id', $media)
            ->firstOrFail();

        return Inertia::render('media/Show', [
            'media' => [
                'hash_id' => $item->hash_id,
                'title' => $item->title,
                'description' => $item->description,
                'source' => $item->source,
                'width' => $item->width,
                'height' => $item->height,
                'filesize' => $item->filesize,
                'mime_type' => $item->mime_type,
                'is_animated' => $item->is_animated,
                'frame_count' => $item->frame_count,
                'dominant_color' => $item->dominant_color,
                'visibility' => $item->visibility->value,
                'safety_rating' => $item->safety_rating->value,
                'is_anonymous' => $item->is_anonymous,
                'has_thumbnail' => $item->thumbnails !== null,
                'tag_count' => $item->tag_count,
                'uploader' => $this->uploader($request, $item),
                'created_at' => $item->created_at?->toIso8601String(),
                'tags' => $item->tags()
                    ->with('category')
                    ->get()
                    ->sortBy(fn (Tag $tag): array => $tag->categorySortKey())
                    ->map(fn ($tag): array => [
                        'name' => $tag->name,
                        'category' => $tag->category?->name,
                        'color' => $tag->category?->color,
                        'usage_count' => $tag->usage_count,
                        'source' => $tag->pivot->source,
                    ])
                    ->values(),
            ],
            'can' => [
                'update' => $request->user()?->can('update', $item) ?? false,
                'delete' => $request->user()?->can('delete', $item) ?? false,
            ],
        ]);
    }

    /**
     * An anonymous item shows no uploader, except to the uploader themselves
     * and to moderators — accountability is preserved, attribution is not.
     */
    private function uploader(Request $request, Media $item): ?string
    {
        if (!$item->is_anonymous) {
            return $item->uploader->displayName();
        }

        $viewer = $request->user();

        if ($viewer !== null && ($viewer->id === $item->user_id || $viewer->can('media.moderate'))) {
            return $item->uploader->displayName();
        }

        return null;
    }
}
