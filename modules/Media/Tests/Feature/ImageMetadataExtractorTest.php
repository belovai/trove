<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickPixel;
use Modules\Media\Contracts\MediaStorage;
use Modules\Media\Contracts\MetadataExtractor;
use Tests\TestCase;

final class ImageMetadataExtractorTest extends TestCase
{
    public function test_it_reads_dimensions_from_a_still_image(): void
    {
        Storage::fake('local');

        $path = app(MediaStorage::class)->storeOriginal(
            UploadedFile::fake()->image('a.jpg', 640, 480),
            'AbCdEfGhIj',
        );

        $metadata = app(MetadataExtractor::class)->extract($path, 'image/jpeg');

        $this->assertSame(640, $metadata->width);
        $this->assertSame(480, $metadata->height);
        $this->assertFalse($metadata->isAnimated);
        $this->assertNull($metadata->frameCount);
    }

    public function test_it_reports_a_dominant_color_as_hex(): void
    {
        Storage::fake('local');

        $path = app(MediaStorage::class)->storeOriginal(
            UploadedFile::fake()->image('a.jpg', 40, 40),
            'AbCdEfGhIj',
        );

        $metadata = app(MetadataExtractor::class)->extract($path, 'image/jpeg');

        $this->assertMatchesRegularExpression('/\A#[0-9A-Fa-f]{6}\z/', $metadata->dominantColor);
    }

    public function test_it_detects_animation_and_counts_frames(): void
    {
        Storage::fake('local');

        $gif = new Imagick;

        foreach (['red', 'blue', 'green'] as $color) {
            $frame = new Imagick;
            $frame->newImage(20, 20, new ImagickPixel($color), 'gif');
            $gif->addImage($frame);
        }

        $file = UploadedFile::fake()->createWithContent('a.gif', $gif->getImagesBlob());

        $path = app(MediaStorage::class)->storeOriginal($file, 'AbCdEfGhIj');
        $metadata = app(MetadataExtractor::class)->extract($path, 'image/gif');

        $this->assertTrue($metadata->isAnimated);
        $this->assertSame(3, $metadata->frameCount);
    }
}
