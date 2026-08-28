<?php

declare(strict_types=1);

namespace Modules\Tag\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Tag\DataObjects\TagName;
use Modules\Tag\DataObjects\TaxonomyDocument;
use Modules\Tag\Exceptions\InvalidTagName;
use Modules\Tag\Exceptions\InvalidTaxonomyEdge;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;

/**
 * Additive by default. `$replace` overwrites the description and category of
 * tags the document names — it never deletes a tag, because deleting a tag
 * would cascade into `media_tag` and silently untag real items. Replacing the
 * collection's content is not something an import should be able to do.
 */
final class ImportTaxonomy
{
    public function __construct(
        private readonly CreateAlias $createAlias,
        private readonly CreateImplication $createImplication,
    ) {}

    /**
     * @return list<string> conflicts, reported rather than silently skipped
     */
    public function handle(TaxonomyDocument $document, bool $replace): array
    {
        $conflicts = [];

        DB::transaction(function () use ($document, $replace, &$conflicts): void {
            foreach ($document->categories as $row) {
                TagCategory::query()->firstOrCreate(
                    ['name' => TagName::from((string) $row['name'])->value],
                    [
                        'color' => (string) ($row['color'] ?? '#888888'),
                        'sort_order' => (int) ($row['sort_order'] ?? 0),
                        'is_default' => false,
                    ],
                );
            }

            foreach ($document->tags as $row) {
                $conflicts = [...$conflicts, ...$this->importTag($row, $replace)];
            }

            foreach ($document->aliases as $row) {
                try {
                    $tag = Tag::query()->where('name', TagName::from((string) $row['tag'])->value)->firstOrFail();
                    $this->createAlias->handle($tag, (string) $row['alias']);
                } catch (InvalidTagName|InvalidTaxonomyEdge $e) {
                    $conflicts[] = $e->translated();
                }
            }

            foreach ($document->implications as $row) {
                try {
                    $tag = Tag::query()->where('name', TagName::from((string) $row['tag'])->value)->firstOrFail();
                    $implied = Tag::query()->where('name', TagName::from((string) $row['implies'])->value)->firstOrFail();
                    $this->createImplication->handle($tag, $implied);
                } catch (InvalidTagName|InvalidTaxonomyEdge $e) {
                    $conflicts[] = $e->translated();
                }
            }
        });

        return $conflicts;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function importTag(array $row, bool $replace): array
    {
        try {
            $name = TagName::from((string) $row['name'])->value;
        } catch (InvalidTagName $e) {
            return [$e->translated()];
        }

        // Normalized before the lookup: categories are stored under a
        // TagName-normalized name, so a document saying "Character" must find
        // `character` rather than silently landing in the default.
        $categoryName = isset($row['category'])
            ? TagName::tryFrom((string) $row['category'])?->value
            : null;

        $category = $categoryName === null
            ? TagCategory::default()
            : TagCategory::query()->where('name', $categoryName)->first() ?? TagCategory::default();

        $tag = Tag::query()->where('name', $name)->first();

        if ($tag === null) {
            Tag::query()->create([
                'name' => $name,
                'category_id' => $category->id,
                'description' => $row['description'] ?? null,
                'usage_count' => 0,
            ]);

            return [];
        }

        if ($replace) {
            $tag->fill([
                'category_id' => $category->id,
                'description' => $row['description'] ?? null,
            ])->save();
        }

        return [];
    }
}
