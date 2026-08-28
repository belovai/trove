<?php

declare(strict_types=1);

namespace Modules\Tag\Actions;

use Modules\Tag\DataObjects\TagName;
use Modules\Tag\Exceptions\InvalidTagName;
use Modules\Tag\Models\TagCategory;

final class UpdateTagCategory
{
    /**
     * @throws InvalidTagName
     */
    public function handle(TagCategory $category, string $rawName, string $color, int $sortOrder): TagCategory
    {
        $category->fill([
            'name' => TagName::from($rawName)->value,
            'color' => $color,
            'sort_order' => $sortOrder,
        ])->save();

        return $category;
    }
}
