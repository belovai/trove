<?php

declare(strict_types=1);

namespace Modules\Media\Support;

use Modules\Media\Models\Media;

/**
 * The public shape of a grid item, shared by browse and the profile page.
 * The internal id and the storage path never leave the server.
 */
final class MediaCardPayload
{
    /**
     * $withAnonymousFlag is only ever true for a viewer entitled to know —
     * the uploader themselves, or a moderator. For everyone else the key is
     * absent rather than false, so no anonymity is leaked by its presence.
     *
     * @return array<string, mixed>
     */
    public static function for(Media $item, bool $withAnonymousFlag = false): array
    {
        $payload = [
            'hash_id' => $item->hash_id,
            'title' => $item->title,
            'width' => $item->width,
            'height' => $item->height,
            'dominant_color' => $item->dominant_color,
            'safety_rating' => $item->safety_rating->value,
            'has_thumbnail' => $item->thumbnails !== null,
            'tag_count' => $item->tag_count,
        ];

        if ($withAnonymousFlag) {
            $payload['is_anonymous'] = $item->is_anonymous;
        }

        return $payload;
    }
}
