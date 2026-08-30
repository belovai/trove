<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\User\Enums\AvatarSource;
use Modules\User\Models\User;
use Tests\TestCase;

final class ServeAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_streams_an_uploaded_avatar(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/account/avatar', [
            'source' => 'upload',
            'avatar' => UploadedFile::fake()->image('me.jpg'),
        ]);

        $response = $this->get('/avatars/'.$user->username);

        $response->assertOk();
        $this->assertSame('image/webp', $response->headers->get('Content-Type'));
    }

    public function test_it_404s_when_the_user_has_no_uploaded_avatar(): void
    {
        $user = User::factory()->create(['avatar_source' => AvatarSource::Letter]);

        $this->get('/avatars/'.$user->username)->assertNotFound();
    }

    public function test_avatar_url_is_null_for_the_letter_source(): void
    {
        $user = User::factory()->create(['avatar_source' => AvatarSource::Letter]);

        $this->assertNull($user->avatarUrl());
    }

    public function test_avatar_url_is_null_for_a_freshly_created_model_without_the_attribute_set(): void
    {
        // Mirrors every factory()->create() call across the suite that
        // doesn't pass avatar_source explicitly: the in-memory model has no
        // cast value yet, even though the column defaults to "letter".
        $user = User::factory()->create();

        $this->assertNull($user->avatarUrl());
    }

    public function test_avatar_url_builds_a_gravatar_link_from_the_email(): void
    {
        $user = User::factory()->create(['avatar_source' => AvatarSource::Gravatar, 'email' => 'Ada@Example.test']);

        $this->assertSame(
            'https://www.gravatar.com/avatar/'.md5('ada@example.test'),
            $user->avatarUrl(),
        );
    }
}
