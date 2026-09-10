<?php

declare(strict_types=1);

namespace Modules\Media\Policies;

use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Modules\User\Enums\UserRank;
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
            if ($media->visibility->notEquals(Visibility::Authenticated)) {
                return true;
            }

            return $user !== null && $user->rank->notEquals(UserRank::Restricted);
        }

        return $this->ownsOrModerates($user, $media);
    }

    /**
     * A vote ranks someone else's item. Two rules beyond the rank gate:
     * you cannot vote on your own upload in either direction, and you cannot
     * vote on what you cannot see.
     *
     * The self-vote rule applies to anonymous uploads as well. The server
     * knows the real user_id, and refusing reveals nothing: the uploader is
     * the only person who ever sees the refusal.
     */
    public function vote(User $user, Media $media): bool
    {
        if ($user->id === $media->user_id) {
            return false;
        }

        return $user->can('media.vote') && $this->view($user, $media);
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
