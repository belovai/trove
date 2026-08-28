<?php

declare(strict_types=1);

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Tag\Models\Tag;
use Modules\User\Models\User;

/**
 * @property int $id
 * @property string $hash_id
 * @property int $user_id
 * @property bool $is_anonymous
 * @property string|null $title
 * @property string|null $description
 * @property string|null $source
 * @property Visibility $visibility
 * @property SafetyRating $safety_rating
 * @property string $original_filename
 * @property string $mime_type
 * @property int $filesize
 * @property int $width
 * @property int $height
 * @property bool $is_animated
 * @property int|null $frame_count
 * @property string $content_hash
 * @property string $storage_path
 * @property array<array-key, mixed>|null $thumbnails
 * @property string|null $dominant_color
 * @property int $tag_count
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Tag> $tags
 * @property-read int|null $tags_count
 * @property-read User|null $uploader
 *
 * @method static \Modules\Media\Database\Factories\MediaFactory factory($count = null, $state = [])
 * @method static Builder<static>|Media listable()
 * @method static Builder<static>|Media newModelQuery()
 * @method static Builder<static>|Media newQuery()
 * @method static Builder<static>|Media onlyTrashed()
 * @method static Builder<static>|Media query()
 * @method static Builder<static>|Media untagged()
 * @method static Builder<static>|Media visibleTo(?\Modules\User\Models\User $viewer)
 * @method static Builder<static>|Media whereContentHash($value)
 * @method static Builder<static>|Media whereCreatedAt($value)
 * @method static Builder<static>|Media whereDeletedAt($value)
 * @method static Builder<static>|Media whereDescription($value)
 * @method static Builder<static>|Media whereDominantColor($value)
 * @method static Builder<static>|Media whereFilesize($value)
 * @method static Builder<static>|Media whereFrameCount($value)
 * @method static Builder<static>|Media whereHashId($value)
 * @method static Builder<static>|Media whereHeight($value)
 * @method static Builder<static>|Media whereId($value)
 * @method static Builder<static>|Media whereIsAnimated($value)
 * @method static Builder<static>|Media whereIsAnonymous($value)
 * @method static Builder<static>|Media whereMimeType($value)
 * @method static Builder<static>|Media whereOriginalFilename($value)
 * @method static Builder<static>|Media whereSafetyRating($value)
 * @method static Builder<static>|Media whereSource($value)
 * @method static Builder<static>|Media whereStoragePath($value)
 * @method static Builder<static>|Media whereTagCount($value)
 * @method static Builder<static>|Media whereThumbnails($value)
 * @method static Builder<static>|Media whereTitle($value)
 * @method static Builder<static>|Media whereUpdatedAt($value)
 * @method static Builder<static>|Media whereUserId($value)
 * @method static Builder<static>|Media whereVisibility($value)
 * @method static Builder<static>|Media whereWidth($value)
 * @method static Builder<static>|Media withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Media withinSafetyFilter(?\Modules\User\Models\User $viewer, ?array $ratings = null)
 * @method static Builder<static>|Media withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable(
    // Request-supplied.
    'is_anonymous',
    'title',
    'description',
    'source',
    'visibility',
    'safety_rating',
    // Server-computed. Never taken from request input, but mass-assigned by
    // StoreUploadedMedia.
    'hash_id',
    'user_id',
    'original_filename',
    'mime_type',
    'filesize',
    'width',
    'height',
    'is_animated',
    'frame_count',
    'content_hash',
    'storage_path',
    'dominant_color',
    'tag_count',
)]
#[Hidden(
    'storage_path',
)]
#[Table('media')]
final class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The route key is the public hash_id: the auto-increment id is never
     * exposed in a URL.
     */
    public function getRouteKeyName(): string
    {
        return 'hash_id';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /**
     * Read-only from the Media module's point of view: every write to the
     * pivot goes through the Tag module's SyncMediaTags.
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'media_tag')
            ->withPivot('source', 'tagged_by')
            ->withTimestamps('created_at', false);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'is_animated' => 'boolean',
            'visibility' => Visibility::class,
            'safety_rating' => SafetyRating::class,
            'thumbnails' => 'array',
        ];
    }

    protected static function newFactory(): MediaFactory
    {
        return MediaFactory::new();
    }

    /**
     * ACCESS CONTROL. The single chokepoint: every query returning media
     * passes through here. Do not bypass it.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, ?User $viewer): void
    {
        if ($viewer !== null && Gate::forUser($viewer)->allows('media.moderate')) {
            return; // Moderators see everything, for moderation.
        }

        $query->where(function (Builder $query) use ($viewer): void {
            $query->whereIn('visibility', [Visibility::Public->value, Visibility::Unlisted->value]);

            if ($viewer !== null) {
                $query->orWhere('visibility', Visibility::Authenticated->value)
                    ->orWhere('user_id', $viewer->id);
            }
        });
    }

    /**
     * LISTING. Unlisted items are reachable by link but never appear in
     * browse or search. Apply this in addition to visibleTo(), never instead.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function listable(Builder $query): void
    {
        $query->where('visibility', '!=', Visibility::Unlisted->value);
    }

    /**
     * DISPLAY FILTER, not access control. It removes items from listings; it
     * never makes one unreachable.
     *
     * $ratings is the viewer's ad-hoc selection. Without one the viewer's
     * stored threshold is expanded to the equivalent set.
     *
     * @param  Builder<self>  $query
     * @param  list<SafetyRating>|null  $ratings
     */
    #[Scope]
    protected function withinSafetyFilter(Builder $query, ?User $viewer, ?array $ratings = null): void
    {
        $ratings ??= SafetyRating::upTo($viewer?->default_safety_filter ?? SafetyRating::Safe);

        $query->whereIn('safety_rating', array_map(
            fn (SafetyRating $rating): string => $rating->value,
            $ratings,
        ));
    }

    /**
     * LISTING FILTER. Items nobody has tagged yet, so they can be found and
     * fixed. Reads the denormalized counter, never the pivot.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function untagged(Builder $query): void
    {
        $query->where('tag_count', 0);
    }
}
