<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Modules\User\Models\User;
use Tests\TestCase;

final class UploadMediaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The batch uploader posts one file per XHR and reads the JSON contract;
     * there is no non-JavaScript form left to serve a redirect to.
     *
     * @var array<string, string>
     */
    private array $json = ['Accept' => 'application/json'];

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get('/upload')->assertRedirect('/login');
    }

    public function test_a_restricted_user_may_not_upload(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->restricted()->create())
            ->post('/upload', $this->payload(), $this->json)
            ->assertForbidden();
    }

    public function test_a_regular_user_can_upload(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->create())
            ->post('/upload', $this->payload(), $this->json)
            ->assertCreated()
            ->assertJsonStructure(['hash_id', 'title']);

        $this->assertSame(1, Media::query()->count());
    }

    public function test_an_oversized_file_is_rejected(): void
    {
        Storage::fake('local');
        config(['trove.media.max_filesize' => 10]); // KB

        $this->actingAs(User::factory()->create())
            ->post('/upload', $this->payload(
                UploadedFile::fake()->image('big.jpg')->size(5000),
            ), $this->json)
            ->assertJsonValidationErrors('file');
    }

    public function test_a_disallowed_type_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->create())
            ->post('/upload', $this->payload(
                UploadedFile::fake()->createWithContent('evil.svg', '<svg onload="alert(1)"></svg>'),
            ), $this->json)
            ->assertJsonValidationErrors('file');
    }

    public function test_a_non_image_renamed_to_jpg_is_rejected(): void
    {
        Storage::fake('local');

        // UploadedFile::fake() always reports a MIME type derived from the
        // extension, never from content, so it cannot exercise content-based
        // sniffing. Construct a real UploadedFile over actual non-image bytes
        // instead, the way Symfony's finfo-backed detection sees it.
        $path = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($path, '<?php echo "hi";');
        $file = new UploadedFile($path, 'payload.jpg', null, null, true);

        $this->actingAs(User::factory()->create())
            ->post('/upload', $this->payload($file), $this->json)
            ->assertJsonValidationErrors('file');
    }

    public function test_anonymous_and_private_cannot_be_combined(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->create())
            ->post('/upload', [...$this->payload(), 'visibility' => 'private', 'is_anonymous' => true], $this->json)
            ->assertJsonValidationErrors('is_anonymous');
    }

    public function test_a_duplicate_is_rejected_under_the_reject_policy(): void
    {
        Storage::fake('local');
        config(['trove.media.duplicate_policy' => 'reject']);

        $user = User::factory()->create();
        $seed = UploadedFile::fake()->image('seed.png');
        $bytes = file_get_contents($seed->getRealPath());
        $file = UploadedFile::fake()->createWithContent('a.png', $bytes);

        Media::factory()->for($user, 'uploader')->create([
            'content_hash' => hash('sha256', $bytes),
        ]);

        $this->actingAs($user)
            ->post('/upload', $this->payload($file), $this->json)
            ->assertJsonValidationErrors('file');
    }

    public function test_the_warn_policy_asks_before_accepting_a_duplicate(): void
    {
        Storage::fake('local');
        config(['trove.media.duplicate_policy' => 'warn']);

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('a.jpg', 400, 300);

        $existing = Media::factory()->for($user, 'uploader')->create([
            'content_hash' => hash_file('sha256', $file->getRealPath()),
        ]);

        // 409, not 422: the file is acceptable, the uploader just has to say
        // whether they meant it. The batch uploader renders this as a per-card
        // prompt and carries on with the rest of the queue.
        $this->actingAs($user)
            ->post('/upload', $this->payload($file), $this->json)
            ->assertStatus(409)
            ->assertJsonPath('duplicate.hash_id', $existing->hash_id);

        $this->assertSame(1, Media::query()->count());
    }

    public function test_confirming_accepts_the_duplicate(): void
    {
        Storage::fake('local');
        config(['trove.media.duplicate_policy' => 'warn']);

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('a.jpg', 400, 300);

        Media::factory()->for($user, 'uploader')->create([
            'content_hash' => hash_file('sha256', $file->getRealPath()),
        ]);

        $this->actingAs($user)
            ->post('/upload', [...$this->payload($file), 'confirm_duplicate' => true], $this->json)
            ->assertCreated();

        $this->assertSame(2, Media::query()->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(?UploadedFile $file = null): array
    {
        return [
            'file' => $file ?? UploadedFile::fake()->image('a.jpg', 400, 300),
            'visibility' => 'public',
            'safety_rating' => 'safe',
            'is_anonymous' => false,
        ];
    }
}
