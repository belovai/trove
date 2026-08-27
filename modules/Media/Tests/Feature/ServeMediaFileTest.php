<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Contracts\MediaStorage;
use Modules\Media\Models\Media;
use Modules\User\Models\User;
use Tests\TestCase;

final class ServeMediaFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_public_file_is_served_with_its_stored_type(): void
    {
        $media = $this->storedMedia();

        $response = $this->get("/m/{$media->hash_id}/file");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_a_private_file_is_a_404_for_a_stranger_not_a_403(): void
    {
        $media = $this->storedMedia(['visibility' => 'private']);

        $this->actingAs(User::factory()->create())
            ->get("/m/{$media->hash_id}/file")
            ->assertNotFound();
    }

    public function test_the_owner_can_fetch_their_private_file(): void
    {
        $owner = User::factory()->create();
        $media = $this->storedMedia(['visibility' => 'private', 'user_id' => $owner->id]);

        $this->actingAs($owner)->get("/m/{$media->hash_id}/file")->assertOk();
    }

    public function test_a_non_public_response_is_not_cacheable_by_proxies(): void
    {
        $owner = User::factory()->create();
        $media = $this->storedMedia(['visibility' => 'private', 'user_id' => $owner->id]);

        $response = $this->actingAs($owner)->get("/m/{$media->hash_id}/file");

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_a_public_response_is_cacheable(): void
    {
        $media = $this->storedMedia();

        $response = $this->get("/m/{$media->hash_id}/file");

        $this->assertStringContainsString('immutable', $response->headers->get('Cache-Control'));
    }

    public function test_a_matching_etag_yields_304(): void
    {
        $media = $this->storedMedia();

        $etag = $this->get("/m/{$media->hash_id}/file")->headers->get('ETag');

        $this->get("/m/{$media->hash_id}/file", ['If-None-Match' => $etag])
            ->assertStatus(304);
    }

    public function test_a_guest_can_fetch_an_unlisted_file_by_link(): void
    {
        // Unlisted means "not listed", not "not accessible". A guest holding
        // the link must get the bytes.
        $media = $this->storedMedia(['visibility' => 'unlisted']);

        $this->get("/m/{$media->hash_id}/file")->assertOk();
    }

    public function test_an_unsafe_item_is_still_served(): void
    {
        // The safety rating is a display filter, not access control.
        $media = $this->storedMedia(['safety_rating' => 'unsafe']);

        $this->get("/m/{$media->hash_id}/file")->assertOk();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function storedMedia(array $attributes = []): Media
    {
        Storage::fake('local');

        $hashId = 'AbCdEfGhIj';
        $path = app(MediaStorage::class)->storeOriginal(
            UploadedFile::fake()->image('a.jpg'),
            $hashId,
        );

        return Media::factory()->create([
            'hash_id' => $hashId,
            'storage_path' => $path,
            'mime_type' => 'image/jpeg',
            ...$attributes,
        ]);
    }
}
