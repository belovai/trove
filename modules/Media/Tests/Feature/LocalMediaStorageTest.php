<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Contracts\MediaStorage;
use Modules\Media\Enums\ThumbnailSize;
use Tests\TestCase;

final class LocalMediaStorageTest extends TestCase
{
    public function test_it_stores_an_original_under_its_hash_id(): void
    {
        Storage::fake('local');

        $path = app(MediaStorage::class)->storeOriginal(
            UploadedFile::fake()->image('holiday photo.jpg'),
            'AbCdEfGhIj',
        );

        $this->assertSame('media/originals/AbCdEfGhIj/original.jpg', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_the_stored_name_never_comes_from_the_upload(): void
    {
        Storage::fake('local');

        $path = app(MediaStorage::class)->storeOriginal(
            UploadedFile::fake()->image('../../etc/passwd.png'),
            'AbCdEfGhIj',
        );

        $this->assertSame('media/originals/AbCdEfGhIj/original.png', $path);
    }

    public function test_it_stores_thumbnails_as_webp(): void
    {
        Storage::fake('local');

        $path = app(MediaStorage::class)->storeThumbnail('AbCdEfGhIj', ThumbnailSize::Thumb, 'binary');

        $this->assertSame('media/thumbnails/AbCdEfGhIj/thumb.webp', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_delete_removes_originals_and_thumbnails(): void
    {
        Storage::fake('local');
        $storage = app(MediaStorage::class);

        $original = $storage->storeOriginal(UploadedFile::fake()->image('a.jpg'), 'AbCdEfGhIj');
        $thumb = $storage->storeThumbnail('AbCdEfGhIj', ThumbnailSize::Thumb, 'binary');

        $storage->delete('AbCdEfGhIj');

        Storage::disk('local')->assertMissing($original);
        Storage::disk('local')->assertMissing($thumb);
    }
}
