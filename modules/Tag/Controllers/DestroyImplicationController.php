<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Tag\Actions\DeleteImplication;
use Modules\Tag\Models\Tag;

final class DestroyImplicationController
{
    public function __construct(
        private readonly DeleteImplication $deleteImplication,
    ) {}

    public function __invoke(Request $request, string $tag, string $implied): RedirectResponse
    {
        $model = Tag::query()->where('name', $tag)->firstOrFail();

        abort_unless($request->user()?->can('update', $model), 403);

        $this->deleteImplication->handle($model, Tag::query()->where('name', $implied)->firstOrFail());

        return back()->with('success', __('tag::tag.implication_removed_rebuild_hint'));
    }
}
