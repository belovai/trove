<?php

declare(strict_types=1);

namespace Modules\Tag\Actions;

use Modules\Tag\DataObjects\TagName;
use Modules\Tag\Exceptions\InvalidTagName;
use Modules\Tag\Exceptions\InvalidTaxonomyEdge;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagAlias;

final class CreateAlias
{
    /**
     * @throws InvalidTagName
     * @throws InvalidTaxonomyEdge
     */
    public function handle(Tag $tag, string $rawAliasName): TagAlias
    {
        $name = TagName::from($rawAliasName)->value;

        // An alias that is also a tag would make resolution ambiguous: the
        // same string would mean two things depending on lookup order.
        if (Tag::query()->where('name', $name)->exists()) {
            throw InvalidTaxonomyEdge::aliasCollidesWithTag($name);
        }

        if (TagAlias::query()->where('alias_name', $name)->exists()) {
            throw InvalidTaxonomyEdge::aliasAlreadyTaken($name);
        }

        return $tag->aliases()->create(['alias_name' => $name]);
    }
}
