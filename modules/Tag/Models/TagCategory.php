<?php

declare(strict_types=1);

namespace Modules\Tag\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Tag\Database\Factories\TagCategoryFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $color
 * @property int $sort_order
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Tag> $tags
 * @property-read int|null $tags_count
 *
 * @method static \Modules\Tag\Database\Factories\TagCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagCategory whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagCategory whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagCategory whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable('name', 'color', 'sort_order', 'is_default')]
final class TagCategory extends Model
{
    /** @use HasFactory<TagCategoryFactory> */
    use HasFactory;

    /**
     * Exactly one row is the default; the schema is seeded with it and
     * DeleteTagCategory refuses to remove it, so this never returns null.
     */
    public static function default(): self
    {
        return self::query()->where('is_default', true)->firstOrFail();
    }

    /**
     * @return HasMany<Tag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class, 'category_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    protected static function newFactory(): TagCategoryFactory
    {
        return TagCategoryFactory::new();
    }
}
