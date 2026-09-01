<?php

declare(strict_types=1);

namespace Modules\Tag\Services;

use Modules\Tag\DataObjects\TaxonomyDocument;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagAlias;
use Modules\Tag\Models\TagCategory;
use Modules\Tag\Models\TagImplication;

final class TaxonomyExporter
{
    public function export(): TaxonomyDocument
    {
        return new TaxonomyDocument(
            categories: array_values(TagCategory::query()->orderBy('name')->get()
                ->map(static fn (TagCategory $category): array => [
                    'name' => $category->name,
                    'color' => $category->color,
                    'sort_order' => $category->sort_order,
                ])->all()),
            tags: array_values(Tag::query()->with('category')->orderBy('name')->get()
                ->map(static fn (Tag $tag): array => [
                    'name' => $tag->name,
                    'category' => $tag->category?->name,
                    'description' => $tag->description,
                ])->all()),
            aliases: array_values(TagAlias::query()->with('tag')->orderBy('alias_name')->get()
                ->map(static fn (TagAlias $alias): array => [
                    'alias' => $alias->alias_name,
                    'tag' => $alias->tag->name,
                ])->all()),
            implications: array_values(TagImplication::query()->with(['tag', 'impliedTag'])->get()
                ->map(static fn (TagImplication $edge): array => [
                    'tag' => $edge->tag->name,
                    'implies' => $edge->impliedTag->name,
                ])->sortBy('tag')->values()->all()),
        );
    }
}
