<?php

declare(strict_types=1);

namespace Modules\Tag\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Media\Models\Media;
use Modules\Tag\Database\Factories\TagFactory;

/**
 * @property int $id
 * @property string $name
 * @property int|null $category_id
 * @property string|null $description
 * @property int $usage_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TagAlias> $aliases
 * @property-read int|null $aliases_count
 * @property-read TagCategory|null $category
 * @property-read Collection<int, Tag> $implications
 * @property-read int|null $implications_count
 * @property-read Collection<int, Media> $media
 * @property-read int|null $media_count
 *
 * @method static \Modules\Tag\Database\Factories\TagFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUsageCount($value)
 *
 * @mixin \Eloquent
 */
#[Fillable('name', 'category_id', 'description', 'usage_count')]
final class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    /**
     * Tags are addressed by name everywhere — URLs, search, autocomplete. The
     * auto-increment id is an implementation detail.
     */
    public function getRouteKeyName(): string
    {
        return 'name';
    }

    /**
     * Sort key that puts tags filed under a real category first, grouped by the
     * category's own order; the default category and uncategorized tags trail.
     *
     * @return array{int, int, string, string}
     */
    public function categorySortKey(): array
    {
        $category = $this->category;
        $filed = $category !== null && !$category->is_default;

        return [
            $filed ? 0 : 1,
            $filed ? $category->sort_order : 0,
            $filed ? $category->name : '',
            $this->name,
        ];
    }

    /**
     * @return BelongsTo<TagCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TagCategory::class, 'category_id');
    }

    /**
     * @return HasMany<TagAlias, $this>
     */
    public function aliases(): HasMany
    {
        return $this->hasMany(TagAlias::class);
    }

    /**
     * The tags this one directly implies. One hop only — the transitive
     * closure belongs to ImplicationClosureResolver.
     *
     * @return BelongsToMany<self, $this>
     */
    public function implications(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'tag_implications', 'tag_id', 'implied_tag_id');
    }

    /**
     * @return BelongsToMany<Media, $this>
     */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_tag')
            ->withPivot('source', 'tagged_by')
            ->withTimestamps('created_at', false);
    }

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }
}
