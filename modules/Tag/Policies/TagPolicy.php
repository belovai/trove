<?php

declare(strict_types=1);

namespace Modules\Tag\Policies;

use Modules\Tag\Models\Tag;
use Modules\User\Models\User;

/**
 * Every ability here is taxonomy-level and therefore gated on tag.manage.
 * Attaching tags to one item is a different question, answered by the
 * tag.edit gate on the media surfaces.
 */
final class TagPolicy
{
    public function update(User $user, Tag $tag): bool
    {
        return $user->can('tag.manage');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->can('tag.manage');
    }

    public function merge(User $user, Tag $tag): bool
    {
        return $user->can('tag.manage');
    }
}
