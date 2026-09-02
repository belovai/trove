<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Modules\Tag\Models\Tag;
use Modules\User\Models\User;

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
                        'source' => $tag->getRelation('pivot')->getAttribute('source'),
                    ])
                    ->values(),
            ],
            'can' => [
                'update' => $request->user()?->can('update', $item) ?? false,
                'delete' => $request->user()?->can('delete', $item) ?? false,
            ],
            'visibilities' => array_map(static fn (Visibility $case): string => $case->value, Visibility::cases()),
        ]);
    }

    /**
     * An anonymous item shows no uploader, except to the uploader themselves
     * and to moderators — accountability is preserved, attribution is not.
     *
     * The pair, not just a name: the media page links to the profile — when
     * the profile is reachable at all.
     *
     * @return array{display_name: string, username: string, linkable: bool}|null
     */
    private function uploader(Request $request, Media $item): ?array
    {
        $uploader = $item->uploader;

        if ($uploader === null) {
            return null;
        }

        $viewer = $request->user();

        if (!$item->is_anonymous) {
            return $this->pair($uploader, $viewer);
        }

        if ($viewer !== null && ($viewer->id === $item->user_id || $viewer->can('media.moderate'))) {
            return $this->pair($uploader, $viewer);
        }

        return null;
    }

    /**
     * `linkable` mirrors ShowProfileController's own gate: false for a
     * soft-deleted account (route model binding 404s it for everyone), and
     * false for a banned one unless the viewer moderates — a moderator can
     * still open a banned profile, so for them the link stays live.
     *
     * @return array{display_name: string, username: string, linkable: bool}
     */
    private function pair(User $uploader, ?User $viewer): array
    {
        $moderates = $viewer !== null && $viewer->can('media.moderate');

        return [
            'display_name' => $uploader->displayName(),
            'username' => $uploader->username,
            'linkable' => $uploader->deleted_at === null && ($uploader->banned_at === null || $moderates),
        ];
    }
}
