<?php

declare(strict_types=1);

namespace Modules\Tag\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $alias_name
 * @property int $tag_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Tag $tag
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagAlias newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagAlias newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagAlias query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagAlias whereAliasName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagAlias whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagAlias whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagAlias whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagAlias whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable('alias_name', 'tag_id')]
final class TagAlias extends Model
{
    /**
     * @return BelongsTo<Tag, $this>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
