<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Modules\Tag\Enums\TagSource;
use Modules\Tag\Models\Tag;

final class EditMediaController
{
    public function __invoke(Request $request, string $media): Response
    {
        $item = Media::query()->visibleTo($request->user())->where('hash_id', $media)->firstOrFail();

        abort_unless($request->user()?->can('update', $item), 403);

        return Inertia::render('media/Edit', [
            'media' => [
                'hash_id' => $item->hash_id,
                'title' => $item->title,
                'description' => $item->description,
                'source' => $item->source,
                'width' => $item->width,
                'height' => $item->height,
                'dominant_color' => $item->dominant_color,
                'visibility' => $item->visibility->value,
                'safety_rating' => $item->safety_rating->value,
                'is_anonymous' => $item->is_anonymous,
                'has_thumbnail' => $item->thumbnails !== null,
                'tag_count' => $item->tag_count,
            ],
            'visibilities' => array_column(Visibility::cases(), 'value'),
            'safety_ratings' => array_column(SafetyRating::cases(), 'value'),
            'tags' => $item->tags()
                ->wherePivot('source', TagSource::Human->value)
                ->with('category')
                ->get()
                ->sortBy(fn (Tag $tag): array => $tag->categorySortKey())
                ->pluck('name')
                ->all(),
        ]);
    }
}
