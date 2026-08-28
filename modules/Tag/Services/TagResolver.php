<?php

declare(strict_types=1);

namespace Modules\Tag\Services;

use Illuminate\Support\Facades\DB;
use Modules\Tag\DataObjects\ParsedTagInput;
use Modules\Tag\DataObjects\TagInputResult;
use Modules\Tag\DataObjects\TagName;
use Modules\Tag\Exceptions\InvalidTagName;
use Modules\Tag\Exceptions\UnknownTagCategory;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagAlias;
use Modules\Tag\Models\TagCategory;

/**
 * Raw user input to canonical tag ids. The boundary the Media module and the
 * future Search module both call, so neither reproduces normalization, alias
 * resolution or namespace parsing.
 */
final class TagResolver
{
    /**
     * Everything resolve() would refuse, without writing anything. Validation
     * runs before the rest of a request is known to succeed, so it must not
     * leave tags behind when a later rule fails.
     *
     * @param  list<string>  $rawNames
     *
     * @throws InvalidTagName
     * @throws UnknownTagCategory
     */
    public function validate(array $rawNames): void
    {
        foreach ($this->parse($rawNames) as [$parsed, $name]) {
            if ($this->find($name) === null) {
                $this->categoryFor($parsed->category);
            }
        }
    }

    /**
     * @param  list<string>  $rawNames
     *
     * @throws InvalidTagName
     * @throws UnknownTagCategory
     */
    public function resolve(array $rawNames): TagInputResult
    {
        return DB::transaction(function () use ($rawNames): TagInputResult {
            $ids = [];
            $warnings = [];

            foreach ($this->parse($rawNames) as [$parsed, $name]) {
                $tag = $this->find($name);

                if ($tag === null) {
                    $tag = $this->create($name, $parsed->category);
                } elseif ($parsed->category !== null) {
                    $warning = $this->warnIfCategoryDiffers($tag, $parsed->category);

                    if ($warning !== null) {
                        $warnings[] = $warning;
                    }
                }

                $ids[] = $tag->id;
            }

            return new TagInputResult(array_values(array_unique($ids)), $warnings);
        });
    }

    /**
     * Blank entries drop out here, so neither caller has to skip them.
     *
     * @param  list<string>  $rawNames
     * @return list<array{ParsedTagInput, string}>
     *
     * @throws InvalidTagName
     */
    private function parse(array $rawNames): array
    {
        $parsed = [];

        foreach ($rawNames as $raw) {
            if (trim($raw) === '') {
                continue;
            }

            $input = ParsedTagInput::parse($raw);

            $parsed[] = [$input, TagName::from($input->name)->value];
        }

        return $parsed;
    }

    /**
     * Canonical tag, or the tag an alias points at. One lookup each: alias
     * chains do not exist, so no loop is needed.
     */
    private function find(string $name): ?Tag
    {
        $tag = Tag::query()->where('name', $name)->first();

        if ($tag !== null) {
            return $tag;
        }

        return TagAlias::query()->where('alias_name', $name)->first()?->tag;
    }

    /**
     * @throws UnknownTagCategory
     */
    private function create(string $name, ?string $categoryName): Tag
    {
        return Tag::query()->create([
            'name' => $name,
            'category_id' => $this->categoryFor($categoryName)->id,
            'usage_count' => 0,
        ]);
    }

    /**
     * @throws UnknownTagCategory
     */
    private function categoryFor(?string $categoryName): TagCategory
    {
        $category = $categoryName === null
            ? TagCategory::default()
            : TagCategory::query()->where('name', $categoryName)->first();

        if ($category === null) {
            throw UnknownTagCategory::named((string) $categoryName);
        }

        return $category;
    }

    /**
     * A prefix on an existing tag never recategorizes it — the category is
     * taxonomy-level data affecting every item carrying the tag, and this is
     * an item-level surface. The user is told, not obeyed.
     */
    private function warnIfCategoryDiffers(Tag $tag, string $categoryName): ?string
    {
        $current = $tag->category?->name;

        if ($current === $categoryName) {
            return null;
        }

        return __('tag::validation.category_prefix_ignored', [
            'name' => $tag->name,
            'category' => $current ?? __('tag::tag.uncategorized'),
        ]);
    }
}
