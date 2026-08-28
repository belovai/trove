<?php

declare(strict_types=1);

namespace Modules\Tag\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;

/**
 * @extends Factory<Tag>
 */
final class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'category_id' => TagCategory::default()->id,
            'description' => null,
            'usage_count' => 0,
        ];
    }
}
