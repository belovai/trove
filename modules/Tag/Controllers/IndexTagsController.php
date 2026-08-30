<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;

final class IndexTagsController
{
    public function __invoke(Request $request): Response
    {
        $query = $request->string('q')->trim()->value();
        $category = $request->string('category')->trim()->value();

        $tags = Tag::query()
            ->with('category')
            ->when($query !== '', fn (Builder $builder): Builder => $builder->whereRaw(
                'LOWER(name) LIKE ?',
                ['%'.mb_strtolower($query).'%'],
            ))
            ->when($category !== '', fn (Builder $builder): Builder => $builder->whereHas(
                'category',
                fn (Builder $categoryQuery): Builder => $categoryQuery->where('name', $category),
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

        $categories = TagCategory::query()
            ->withCount('tags')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (TagCategory $tagCategory): bool => $tagCategory->tags_count > 0)
            ->map(fn (TagCategory $tagCategory): array => [
                'name' => $tagCategory->name,
                'color' => $tagCategory->color,
                'tags_count' => $tagCategory->tags_count,
            ])
            ->values();

        return Inertia::render('tags/Index', [
            'tags' => $tags,
            'categories' => $categories,
            'filters' => ['q' => $query, 'category' => $category !== '' ? $category : null],
        ]);
    }
}
