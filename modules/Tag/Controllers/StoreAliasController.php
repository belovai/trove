<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Tag\Actions\CreateAlias;
use Modules\Tag\Exceptions\InvalidTagName;
use Modules\Tag\Exceptions\InvalidTaxonomyEdge;
use Modules\Tag\Models\Tag;
use Modules\Tag\Requests\CreateAliasRequest;

final class StoreAliasController
{
    public function __construct(
        private readonly CreateAlias $createAlias,
    ) {}

    public function __invoke(CreateAliasRequest $request, string $tag): RedirectResponse
    {
        $model = Tag::query()->where('name', $tag)->firstOrFail();

        abort_unless($request->user()?->can('update', $model), 403);

        try {
            $this->createAlias->handle($model, $request->string('alias')->value());
        } catch (InvalidTagName|InvalidTaxonomyEdge $e) {
            return back()->withErrors(['alias' => $e->translated()]);
        }

        return back()->with('success', __('tag::tag.alias_added'));
    }
}
