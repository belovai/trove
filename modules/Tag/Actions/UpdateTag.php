<?php

declare(strict_types=1);

namespace Modules\Tag\Actions;

use Modules\Tag\DataObjects\TagName;
use Modules\Tag\Exceptions\InvalidTagName;
use Modules\Tag\Exceptions\InvalidTaxonomyEdge;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagAlias;

/**
 * The taxonomy-level editor. This — and the admin list — are the only places a
 * tag's category can change; no media surface can do it.
 */
final class UpdateTag
{
    /**
     * @throws InvalidTagName
     * @throws InvalidTaxonomyEdge
     */
    public function handle(Tag $tag, string $rawName, ?int $categoryId, ?string $description): Tag
    {
        $name = TagName::from($rawName)->value;

        if ($name !== $tag->name) {
            if (Tag::query()->where('name', $name)->whereKeyNot($tag->id)->exists()) {
                throw InvalidTaxonomyEdge::aliasCollidesWithTag($name);
            }

            if (TagAlias::query()->where('alias_name', $name)->exists()) {
                throw InvalidTaxonomyEdge::aliasAlreadyTaken($name);
            }
        }

        $tag->fill([
            'name' => $name,
            'category_id' => $categoryId,
            'description' => $description === '' ? null : $description,
        ])->save();

        return $tag;
    }
}
