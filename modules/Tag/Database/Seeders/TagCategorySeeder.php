<?php

declare(strict_types=1);

namespace Modules\Tag\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tag\Models\TagCategory;

/**
 * Optional starter taxonomy. The `general` category is created by the
 * migration because the application cannot run without a default; everything
 * here is a convenience for local development and may be ignored or edited.
 */
final class TagCategorySeeder extends Seeder
{
    /**
     * @var list<array{name: string, color: string, sort_order: int}>
     */
    private const array CATEGORIES = [
        ['name' => 'artist', 'color' => '#c00004', 'sort_order' => 1],
        ['name' => 'copyright', 'color' => '#a800aa', 'sort_order' => 2],
        ['name' => 'character', 'color' => '#00ab2c', 'sort_order' => 3],
        ['name' => 'species', 'color' => '#ed5d1f', 'sort_order' => 4],
        ['name' => 'meta', 'color' => '#fd9200', 'sort_order' => 5],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            TagCategory::query()->firstOrCreate(
                ['name' => $category['name']],
                [
                    'color' => $category['color'],
                    'sort_order' => $category['sort_order'],
                    'is_default' => false,
                ],
            );
        }
    }
}
