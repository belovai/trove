<?php

declare(strict_types=1);

namespace Modules\Media\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Modules\User\Models\User;

/**
 * @extends Factory<Media>
 */
final class MediaFactory extends Factory
{
    protected $model = Media::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hashId = Str::random(10);

        return [
            'hash_id' => $hashId,
            'user_id' => User::factory(),
            'is_anonymous' => false,
            'title' => $this->faker->sentence(3),
            'description' => null,
            'source' => null,
            'visibility' => Visibility::Public,
            'safety_rating' => SafetyRating::Safe,
            'original_filename' => 'example.jpg',
            'mime_type' => 'image/jpeg',
            'filesize' => 12345,
            'width' => 800,
            'height' => 600,
            'is_animated' => false,
            'frame_count' => null,
            'content_hash' => hash('sha256', $this->faker->unique()->uuid()),
            'storage_path' => "media/originals/{$hashId}/original.jpg",
            'thumbnails' => null,
            'dominant_color' => '#A3C4F3',
            'tag_count' => 0,
        ];
    }

    public function private(): self
    {
        return $this->state(fn (): array => ['visibility' => Visibility::Private]);
    }

    public function unlisted(): self
    {
        return $this->state(fn (): array => ['visibility' => Visibility::Unlisted]);
    }

    public function authenticatedOnly(): self
    {
        return $this->state(fn (): array => ['visibility' => Visibility::Authenticated]);
    }

    public function unsafe(): self
    {
        return $this->state(fn (): array => ['safety_rating' => SafetyRating::Unsafe]);
    }
}
