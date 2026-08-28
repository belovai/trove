<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Tag\Actions\CreateImplication;
use Modules\Tag\Exceptions\InvalidTaxonomyEdge;
use Modules\Tag\Models\Tag;
use Modules\Tag\Requests\CreateImplicationRequest;

final class StoreImplicationController
{
    public function __construct(
        private readonly CreateImplication $createImplication,
    ) {}

    public function __invoke(CreateImplicationRequest $request, string $tag): RedirectResponse
    {
        $model = Tag::query()->where('name', $tag)->firstOrFail();

        abort_unless($request->user()?->can('update', $model), 403);

        $implied = Tag::query()->where('name', $request->string('implies')->value())->firstOrFail();

        try {
            $this->createImplication->handle($model, $implied);
        } catch (InvalidTaxonomyEdge $e) {
            return back()->withErrors(['implies' => $e->translated()]);
        }

        // Existing media is not retroactively updated by adding an edge.
        return back()->with('success', __('tag::tag.implication_added_rebuild_hint'));
    }
}
