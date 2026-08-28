<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagAlias;

final class AutocompleteTagsController
{
    private const LIMIT = 15;

    /**
     * Matches tag names and alias names in one list. `matched` tells the UI
     * which string produced the hit, so it can show "kitty → cat" instead of
     * silently substituting.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = mb_strtolower($request->string('q')->trim()->value());

        if ($query === '') {
            return response()->json([]);
        }

        $tags = Tag::query()->with('category')
            ->whereRaw('LOWER(name) LIKE ?', [$query.'%'])
            ->orderByDesc('usage_count')
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Tag $tag): array => $this->present($tag, $tag->name));

        $aliases = TagAlias::query()->with('tag.category')
            ->whereRaw('LOWER(alias_name) LIKE ?', [$query.'%'])
            ->limit(self::LIMIT)
            ->get()
            ->reject(fn (TagAlias $alias): bool => $tags->contains(
                fn (array $row): bool => $row['name'] === $alias->tag->name,
            ))
            ->map(fn (TagAlias $alias): array => $this->present($alias->tag, $alias->alias_name));

        return response()->json(
            $tags->concat($aliases)
                ->sortByDesc('usage_count')
                ->take(self::LIMIT)
                ->values(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Tag $tag, string $matched): array
    {
        return [
            'name' => $tag->name,
            'matched' => $matched,
            'category' => $tag->category?->name,
            'color' => $tag->category?->color,
            'usage_count' => $tag->usage_count,
            'description' => $tag->description,
        ];
    }
}
