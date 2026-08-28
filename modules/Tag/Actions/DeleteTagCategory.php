<?php

declare(strict_types=1);

namespace Modules\Tag\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;

final class DeleteTagCategory
{
    /**
     * Tags are reassigned to the default rather than left uncategorized:
     * losing a category should not silently degrade every tag in it. The
     * policy refuses to delete the default itself, so a destination always
     * exists.
     */
    public function handle(TagCategory $category): void
    {
        DB::transaction(function () use ($category): void {
            Tag::query()->where('category_id', $category->id)
                ->update(['category_id' => TagCategory::default()->id]);

            $category->delete();
        });
    }
}
