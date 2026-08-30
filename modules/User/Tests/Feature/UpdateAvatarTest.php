<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\User\Enums\AvatarSource;
use Modules\User\Models\User;
use Tests\TestCase;

final class UpdateAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_upload_an_avatar(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['avatar_source' => AvatarSource::Letter, 'avatar_path' => null]);

        $this->actingAs($user)
            ->patch('/account/avatar', [
                'source' => 'upload',
                'avatar' => UploadedFile::fake()->image('me.jpg', 800, 800),
            ])
            ->assertRedirect('/settings/profile');

        $user->refresh();

        $this->assertSame(AvatarSource::Upload, $user->avatar_source);
        $this->assertNotNull($user->avatar_path);
        Storage::disk('local')->assertExists($user->avatar_path);
    }

    public function test_uploading_without_a_file_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/account/avatar', ['source' => 'upload'])
            ->assertSessionHasErrors('avatar');
    }

    public function test_a_user_can_switch_to_gravatar(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.test', 'avatar_source' => AvatarSource::Letter]);

        $this->actingAs($user)
            ->patch('/account/avatar', ['source' => 'gravatar'])
            ->assertRedirect('/settings/profile');

        $this->assertSame(AvatarSource::Gravatar, $user->fresh()->avatar_source);
    }

    public function test_gravatar_is_rejected_without_an_email(): void
    {
        $user = User::factory()->create(['email' => null, 'avatar_source' => AvatarSource::Letter]);

        $this->actingAs($user)
            ->patch('/account/avatar', ['source' => 'gravatar'])
            ->assertSessionHasErrors('source');

        $this->assertSame(AvatarSource::Letter, $user->fresh()->avatar_source);
    }

    public function test_a_user_can_revert_to_the_letter_avatar_and_the_file_is_removed(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/account/avatar', [
            'source' => 'upload',
            'avatar' => UploadedFile::fake()->image('me.jpg'),
        ]);

        $path = $user->fresh()->avatar_path;
        Storage::disk('local')->assertExists($path);

        $this->actingAs($user)
            ->patch('/account/avatar', ['source' => 'letter'])
            ->assertRedirect('/settings/profile');

        $user->refresh();
        $this->assertSame(AvatarSource::Letter, $user->avatar_source);
        $this->assertNull($user->avatar_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_a_guest_cannot_update_the_avatar(): void
    {
        $this->patch('/account/avatar', ['source' => 'letter'])->assertRedirect('/login');
    }
}
