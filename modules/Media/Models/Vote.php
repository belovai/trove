<?php

declare(strict_types=1);

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Media\Enums\VoteValue;
use Modules\User\Models\User;

/**
 * @property int $id
 * @property int $user_id
 * @property int $media_id
 * @property VoteValue $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Media|null $media
 * @property-read User|null $voter
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vote whereValue($value)
 *
 * @mixin \Eloquent
 */
#[Fillable('user_id', 'media_id', 'value')]
#[Table('votes')]
final class Vote extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function voter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => VoteValue::class,
        ];
    }
}
