<?php

declare(strict_types=1);

namespace Modules\Media\Policies;

use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Modules\User\Models\User;

final class MediaPolicy
{
    /**
     * Mirrors the visibleTo() scope for a single record. The scope filters
     * lists; this authorizes one item. They must agree.
     */
    public function view(?User $user, Media $media): bool
    {
        if ($media->visibility->notEquals(Visibility::Private)) {
            return $media->visibility->notEquals(Visibility::Authenticated) || $user !== null;
        }

        return $this->ownsOrModerates($user, $media);
    }

    public function update(User $user, Media $media): bool
    {
        return $this->ownsOrModerates($user, $media);
    }

    public function delete(User $user, Media $media): bool
    {
        return $this->ownsOrModerates($user, $media);
    }

    private function ownsOrModerates(?User $user, Media $media): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->id === $media->user_id || $user->can('media.moderate');
    }
}
