<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Tag\Actions\UpdateTagCategory;
use Modules\Tag\Exceptions\InvalidTagName;
use Modules\Tag\Models\TagCategory;
use Modules\Tag\Requests\TagCategoryRequest;

final class UpdateTagCategoryController
{
    public function __construct(
        private readonly UpdateTagCategory $updateTagCategory,
    ) {}

    public function __invoke(TagCategoryRequest $request, TagCategory $category): RedirectResponse
    {
        abort_unless($request->user()?->can('update', $category), 403);

        try {
            $this->updateTagCategory->handle(
                category: $category,
                rawName: $request->string('name')->value(),
                color: $request->string('color')->value(),
                sortOrder: $request->integer('sort_order'),
            );
        } catch (InvalidTagName $e) {
            return back()->withErrors(['name' => $e->translated()]);
        }

        return back()->with('success', __('tag::tag.category_updated'));
    }
}
