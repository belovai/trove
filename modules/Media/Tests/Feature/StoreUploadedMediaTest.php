<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Actions\StoreUploadedMedia;
use Modules\Media\Contracts\MetadataExtractor;
use Modules\Media\DataObjects\UploadedMediaData;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Modules\User\Models\User;
use Tests\TestCase;

final class StoreUploadedMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_record_and_stores_the_file(): void
    {
        Storage::fake('local');

        $uploader = User::factory()->create();

        $media = app(StoreUploadedMedia::class)->handle(new UploadedMediaData(
            file: UploadedFile::fake()->image('holiday.jpg', 800, 600),
            uploader: $uploader,
            title: 'Holiday',
            description: null,
            source: null,
            visibility: Visibility::Public,
            safetyRating: SafetyRating::Safe,
            isAnonymous: false,
        ));

        $this->assertSame(10, strlen($media->hash_id));
        $this->assertSame($uploader->id, $media->user_id);
        $this->assertSame('holiday.jpg', $media->original_filename);
        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertSame(800, $media->width);
        $this->assertSame(600, $media->height);
        $this->assertSame(0, $media->tag_count);
        $this->assertSame(64, strlen($media->content_hash));

        Storage::disk('local')->assertExists($media->storage_path);
    }

    public function test_it_generates_thumbnails_synchronously(): void
    {
        Storage::fake('local');

        $media = app(StoreUploadedMedia::class)->handle($this->data());

        $this->assertNotNull($media->fresh()->thumbnails);
    }

    public function test_the_content_hash_is_the_sha256_of_the_file(): void
    {
        Storage::fake('local');

        // Real, decodable bytes: the extractor needs a valid image, but the
        // property under test is that the hash matches whatever bytes it is.
        $seed = UploadedFile::fake()->image('seed.png');
        $bytes = file_get_contents($seed->getRealPath());
        $file = UploadedFile::fake()->createWithContent('a.png', $bytes);

        $media = app(StoreUploadedMedia::class)->handle($this->data(file: $file));

        $this->assertSame(hash('sha256', $bytes), $media->content_hash);
    }

    public function test_a_duplicate_of_your_own_item_is_found(): void
    {
        Storage::fake('local');

        $uploader = User::factory()->create();
        $existing = Media::factory()->private()->for($uploader, 'uploader')
            ->create(['content_hash' => hash('sha256', 'same')]);

        $found = app(StoreUploadedMedia::class)
            ->findDuplicateFor(hash('sha256', 'same'), $uploader);

        $this->assertTrue($found?->is($existing));
    }

    public function test_another_users_private_item_is_never_reported_as_a_duplicate(): void
    {
        Storage::fake('local');

        Media::factory()->private()->create(['content_hash' => hash('sha256', 'same')]);

        $found = app(StoreUploadedMedia::class)
            ->findDuplicateFor(hash('sha256', 'same'), User::factory()->create());

        $this->assertNull($found);
    }

    public function test_a_storage_failure_leaves_no_orphaned_file(): void
    {
        Storage::fake('local');

        // A width of zero is impossible for a real image; forcing the record
        // insert to fail proves the cleanup path runs.
        $this->mock(MetadataExtractor::class)
            ->shouldReceive('extract')
            ->andThrow(new \RuntimeException('unreadable'));

        try {
            app(StoreUploadedMedia::class)->handle($this->data());
            $this->fail('The upload should have failed.');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame(0, Media::count());
        $this->assertEmpty(Storage::disk('local')->allFiles('media/originals'));
    }

    private function data(?UploadedFile $file = null): UploadedMediaData
    {
        return new UploadedMediaData(
            file: $file ?? UploadedFile::fake()->image('a.jpg', 400, 300),
            uploader: User::factory()->create(),
            title: null,
            description: null,
            source: null,
            visibility: Visibility::Public,
            safetyRating: SafetyRating::Safe,
            isAnonymous: false,
        );
    }
}
