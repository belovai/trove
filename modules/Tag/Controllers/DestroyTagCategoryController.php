<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Tag\Actions\DeleteTagCategory;
use Modules\Tag\Models\TagCategory;

final class DestroyTagCategoryController
{
    public function __construct(
        private readonly DeleteTagCategory $deleteTagCategory,
    ) {}

    public function __invoke(Request $request, TagCategory $category): RedirectResponse
    {
        abort_unless($request->user()?->can('delete', $category) ?? false, 403);

        $this->deleteTagCategory->handle($category);

        return back()->with('success', __('tag::tag.category_deleted'));
    }
}
