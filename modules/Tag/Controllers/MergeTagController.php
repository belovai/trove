<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Tag\Actions\MergeTags;
use Modules\Tag\Models\Tag;
use Modules\Tag\Requests\MergeTagRequest;

final class MergeTagController
{
    public function __construct(
        private readonly MergeTags $mergeTags,
    ) {}

    public function __invoke(MergeTagRequest $request, string $tag): RedirectResponse
    {
        $source = Tag::query()->where('name', $tag)->firstOrFail();

        abort_unless($request->user()?->can('merge', $source) ?? false, 403);

        $target = Tag::query()->where('name', $request->string('into')->value())->firstOrFail();

        $this->mergeTags->handle($source, $target);

        return redirect()->route('tags.show', $target->name)
            ->with('success', __('tag::tag.merged'));
    }
}
