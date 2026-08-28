<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Modules\User\Models\User;
use Tests\TestCase;

final class MediaModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_with_cast_enums(): void
    {
        $media = Media::factory()->create([
            'visibility' => Visibility::Unlisted,
            'safety_rating' => SafetyRating::Sketchy,
        ]);

        $media->refresh();

        $this->assertInstanceOf(Visibility::class, $media->visibility);
        $this->assertTrue($media->visibility->equals(Visibility::Unlisted));
        $this->assertTrue($media->safety_rating->equals(SafetyRating::Sketchy));
    }

    public function test_the_route_key_is_the_hash_id(): void
    {
        $this->assertSame('hash_id', (new Media)->getRouteKeyName());
    }

    public function test_it_belongs_to_an_uploader(): void
    {
        $user = User::factory()->create();
        $media = Media::factory()->for($user, 'uploader')->create();

        $this->assertTrue($media->uploader->is($user));
    }

    public function test_duplicate_content_hashes_are_allowed(): void
    {
        Media::factory()->create(['content_hash' => str_repeat('a', 64)]);
        Media::factory()->create(['content_hash' => str_repeat('a', 64)]);

        $this->assertSame(2, Media::query()->where('content_hash', str_repeat('a', 64))->count());
    }

    public function test_deleting_is_soft(): void
    {
        $media = Media::factory()->create();
        $media->delete();

        $this->assertSoftDeleted($media);
    }
}
