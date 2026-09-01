<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Tag\Actions\UpdateTag;
use Modules\Tag\Exceptions\InvalidTaxonomyEdge;
use Modules\Tag\Models\Tag;
use Modules\Tag\Requests\UpdateTagRequest;

final class UpdateTagController
{
    public function __construct(
        private readonly UpdateTag $updateTag,
    ) {}

    public function __invoke(UpdateTagRequest $request, string $tag): RedirectResponse
    {
        $model = Tag::query()->where('name', $tag)->firstOrFail();

        abort_unless($request->user()?->can('update', $model) ?? false, 403);

        try {
            $this->updateTag->handle(
                tag: $model,
                rawName: $request->string('name')->value(),
                categoryId: $request->integer('category_id') ?: null,
                description: $request->input('description'),
            );
        } catch (InvalidTaxonomyEdge $e) {
            return back()->withErrors(['name' => $e->translated()]);
        }

        return redirect()->route('tags.show', $model->name)
            ->with('success', __('tag::tag.updated'));
    }
}
