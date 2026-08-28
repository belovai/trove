<?php

declare(strict_types=1);

namespace Modules\Tag\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tag\Models\TagCategory;

/**
 * @extends Factory<TagCategory>
 */
final class TagCategoryFactory extends Factory
{
    protected $model = TagCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'color' => $this->faker->hexColor(),
            'sort_order' => 0,
            'is_default' => false,
        ];
    }
}
