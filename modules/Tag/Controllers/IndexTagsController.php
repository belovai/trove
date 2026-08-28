<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tag\Models\Tag;

final class IndexTagsController
{
    public function __invoke(Request $request): Response
    {
        $query = $request->string('q')->trim()->value();

        $tags = Tag::query()
            ->with('category')
            ->when($query !== '', fn (Builder $builder): Builder => $builder->whereRaw(
                'LOWER(name) LIKE ?',
                ['%'.mb_strtolower($query).'%'],
            ))
            ->orderByDesc('usage_count')
            ->orderBy('name')
            ->paginate(60)
            ->withQueryString()
            ->through(fn (Tag $tag): array => [
                'name' => $tag->name,
                'category' => $tag->category?->name,
                'color' => $tag->category?->color,
                'usage_count' => $tag->usage_count,
            ]);

        return Inertia::render('tags/Index', [
            'tags' => $tags,
            'filters' => ['q' => $query],
        ]);
    }
}
