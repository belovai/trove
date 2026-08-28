<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Tag\Actions\CreateTagCategory;
use Modules\Tag\Exceptions\InvalidTagName;
use Modules\Tag\Models\TagCategory;
use Modules\Tag\Requests\TagCategoryRequest;

final class StoreTagCategoryController
{
    public function __construct(
        private readonly CreateTagCategory $createTagCategory,
    ) {}

    public function __invoke(TagCategoryRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->can('create', TagCategory::class), 403);

        try {
            $this->createTagCategory->handle(
                rawName: $request->string('name')->value(),
                color: $request->string('color')->value(),
                sortOrder: $request->integer('sort_order'),
            );
        } catch (InvalidTagName $e) {
            return back()->withErrors(['name' => $e->translated()]);
        }

        return back()->with('success', __('tag::tag.category_created'));
    }
}
