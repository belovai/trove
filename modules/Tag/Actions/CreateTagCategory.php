<?php

declare(strict_types=1);

namespace Modules\Tag\Actions;

use Modules\Tag\DataObjects\TagName;
use Modules\Tag\Exceptions\InvalidTagName;
use Modules\Tag\Models\TagCategory;

final class CreateTagCategory
{
    /**
     * Category names are normalized like tag names: they appear as the
     * `category:` prefix in tag input, so the same grammar applies.
     *
     * @throws InvalidTagName
     */
    public function handle(string $rawName, string $color, int $sortOrder): TagCategory
    {
        return TagCategory::query()->create([
            'name' => TagName::from($rawName)->value,
            'color' => $color,
            'sort_order' => $sortOrder,
            'is_default' => false,
        ]);
    }
}
