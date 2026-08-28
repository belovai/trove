<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Tag\Actions\DeleteTag;
use Modules\Tag\Models\Tag;

final class DestroyTagController
{
    public function __construct(
        private readonly DeleteTag $deleteTag,
    ) {}

    public function __invoke(Request $request, string $tag): RedirectResponse
    {
        $model = Tag::query()->where('name', $tag)->firstOrFail();

        abort_unless($request->user()?->can('delete', $model), 403);

        $this->deleteTag->handle($model);

        return redirect()->route('tags.index')->with('success', __('tag::tag.deleted'));
    }
}
