<?php

declare(strict_types=1);

namespace Modules\Tag\Policies;

use Modules\Tag\Models\TagCategory;
use Modules\User\Models\User;

final class TagCategoryPolicy
{
    public function create(User $user): bool
    {
        return $user->can('tag.admin');
    }

    public function update(User $user, TagCategory $category): bool
    {
        return $user->can('tag.admin');
    }

    /**
     * The default category is where new tags land and where a deleted
     * category's tags are reassigned. Removing it would leave the application
     * without a valid destination.
     */
    public function delete(User $user, TagCategory $category): bool
    {
        return $user->can('tag.admin') && !$category->is_default;
    }
}
