<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Modules\Media\Contracts\MediaStorage;
use Modules\Media\Contracts\ThumbnailGenerator;
use Modules\Media\Jobs\GenerateMediaThumbnails;
use Modules\Media\Models\Media;
use Tests\TestCase;

final class ThumbnailGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_writes_both_sizes_and_records_them(): void
    {
        Storage::fake('local');

        $path = app(MediaStorage::class)->storeOriginal(
            UploadedFile::fake()->image('a.jpg', 1200, 900),
            'AbCdEfGhIj',
        );

        $media = Media::factory()->create([
            'hash_id' => 'AbCdEfGhIj',
            'storage_path' => $path,
            'mime_type' => 'image/jpeg',
            'thumbnails' => null,
        ]);

        (new GenerateMediaThumbnails($media))->handle(app(MediaStorage::class), app(ThumbnailGenerator::class));

        $media->refresh();

        $this->assertSame([
            'thumb' => 'media/thumbnails/AbCdEfGhIj/thumb.webp',
            'preview' => 'media/thumbnails/AbCdEfGhIj/preview.webp',
        ], $media->thumbnails);

        Storage::disk('local')->assertExists('media/thumbnails/AbCdEfGhIj/thumb.webp');
        Storage::disk('local')->assertExists('media/thumbnails/AbCdEfGhIj/preview.webp');
    }

    public function test_the_thumb_is_a_square_crop_and_the_preview_keeps_its_ratio(): void
    {
        Storage::fake('local');

        $path = app(MediaStorage::class)->storeOriginal(
            UploadedFile::fake()->image('a.jpg', 1200, 600),
            'AbCdEfGhIj',
        );

        $media = Media::factory()->create([
            'hash_id' => 'AbCdEfGhIj',
            'storage_path' => $path,
            'mime_type' => 'image/jpeg',
        ]);

        (new GenerateMediaThumbnails($media))->handle(app(MediaStorage::class), app(ThumbnailGenerator::class));

        $manager = app(ImageManager::class);

        $thumb = $manager->read(Storage::disk('local')->path('media/thumbnails/AbCdEfGhIj/thumb.webp'));
        $this->assertSame(150, $thumb->width());
        $this->assertSame(150, $thumb->height());

        $preview = $manager->read(Storage::disk('local')->path('media/thumbnails/AbCdEfGhIj/preview.webp'));
        $this->assertSame(850, $preview->width());
        $this->assertSame(425, $preview->height());
    }
}
