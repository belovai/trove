<?php

declare(strict_types=1);

namespace Modules\Tag\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tag_id
 * @property int $implied_tag_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Tag $impliedTag
 * @property-read Tag $tag
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagImplication newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagImplication newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagImplication query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagImplication whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagImplication whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagImplication whereImpliedTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagImplication whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagImplication whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable('tag_id', 'implied_tag_id')]
final class TagImplication extends Model
{
    /**
     * @return BelongsTo<Tag, $this>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    /**
     * @return BelongsTo<Tag, $this>
     */
    public function impliedTag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'implied_tag_id');
    }
}
