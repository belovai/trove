<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Media;
use Modules\Media\Services\HashIdGenerator;
use Tests\TestCase;

final class HashIdGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_produces_ten_base62_characters(): void
    {
        $id = app(HashIdGenerator::class)->generate();

        $this->assertSame(10, strlen($id));
        $this->assertMatchesRegularExpression('/\A[0-9A-Za-z]{10}\z/', $id);
    }

    public function test_it_never_returns_an_id_already_in_use(): void
    {
        $taken = Media::factory()->create()->hash_id;

        for ($i = 0; $i < 20; $i++) {
            $this->assertNotSame($taken, app(HashIdGenerator::class)->generate());
        }
    }

    public function test_a_soft_deleted_id_stays_reserved(): void
    {
        $media = Media::factory()->create();
        $media->delete();

        for ($i = 0; $i < 20; $i++) {
            $this->assertNotSame($media->hash_id, app(HashIdGenerator::class)->generate());
        }
    }
}
