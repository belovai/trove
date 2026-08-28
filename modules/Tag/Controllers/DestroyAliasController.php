<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Tag\Actions\DeleteAlias;
use Modules\Tag\Models\Tag;

final class DestroyAliasController
{
    public function __construct(
        private readonly DeleteAlias $deleteAlias,
    ) {}

    public function __invoke(Request $request, string $tag, string $alias): RedirectResponse
    {
        $model = Tag::query()->where('name', $tag)->firstOrFail();

        abort_unless($request->user()?->can('update', $model), 403);

        $this->deleteAlias->handle($model->aliases()->where('alias_name', $alias)->firstOrFail());

        return back()->with('success', __('tag::tag.alias_removed'));
    }
}
