<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Modules\Media\Models\Favorite;
use Modules\Media\Models\Media;
use Modules\User\Models\User;

/**
 * A plain on/off, unlike CastVote: no second column to keep in sync, so
 * neither method needs a transaction.
 */
final class ToggleFavorite
{
    public function add(Media $media, User $user): void
    {
        Favorite::query()->firstOrCreate([
            'media_id' => $media->id,
            'user_id' => $user->id,
        ]);
    }

    public function remove(Media $media, User $user): void
    {
        Favorite::query()
            ->where('media_id', $media->id)
            ->where('user_id', $user->id)
            ->delete();
    }
}
