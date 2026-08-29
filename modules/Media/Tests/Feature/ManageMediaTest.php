<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Enums\Visibility;
use Modules\Media\Models\Media;
use Modules\User\Models\User;
use Tests\TestCase;

final class ManageMediaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function mediaOwnedBySignedInUser(array $attributes = []): Media
    {
        $owner = User::factory()->create();
        $media = Media::factory()->for($owner, 'uploader')->create($attributes);

        $this->actingAs($owner);

        return $media;
    }

    public function test_the_owner_can_edit_metadata(): void
    {
        $owner = User::factory()->create();
        $media = Media::factory()->for($owner, 'uploader')->create(['title' => 'Old']);

        $this->actingAs($owner)
            ->patch("/m/{$media->hash_id}", [
                'title' => 'New',
                'description' => 'A description',
                'source' => null,
                'visibility' => 'unlisted',
                'safety_rating' => 'sketchy',
                'is_anonymous' => false,
            ])
            ->assertRedirect("/m/{$media->hash_id}");

        $media->refresh();

        $this->assertSame('New', $media->title);
        $this->assertSame('unlisted', $media->visibility->value);
    }

    public function test_a_stranger_cannot_edit(): void
    {
        $media = Media::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patch("/m/{$media->hash_id}", ['title' => 'Mine now', 'visibility' => 'public', 'safety_rating' => 'safe'])
            ->assertForbidden();
    }

    public function test_a_moderator_can_edit(): void
    {
        $media = Media::factory()->create();

        $this->actingAs(User::factory()->moderator()->create())
            ->patch("/m/{$media->hash_id}", [
                'title' => 'Moderated',
                'description' => null,
                'source' => null,
                'visibility' => 'public',
                'safety_rating' => 'safe',
                'is_anonymous' => false,
            ])
            ->assertRedirect();

        $this->assertSame('Moderated', $media->fresh()->title);
    }

    public function test_deleting_is_soft_and_keeps_the_file(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $media = Media::factory()->for($owner, 'uploader')->create();
        Storage::disk('local')->put($media->storage_path, 'bytes');

        $this->actingAs($owner)
            ->delete("/m/{$media->hash_id}")
            ->assertRedirect('/posts');

        $this->assertSoftDeleted($media);
        Storage::disk('local')->assertExists($media->storage_path);
    }

    public function test_a_deleted_item_leaves_browse_and_404s(): void
    {
        $media = Media::factory()->create();
        $media->delete();

        $this->get("/m/{$media->hash_id}")->assertNotFound();
    }

    public function test_editing_cannot_make_a_private_item_anonymous(): void
    {
        $owner = User::factory()->create();
        $media = Media::factory()->for($owner, 'uploader')->create();

        $this->actingAs($owner)
            ->patch("/m/{$media->hash_id}", [
                'title' => null,
                'description' => null,
                'source' => null,
                'visibility' => 'private',
                'safety_rating' => 'safe',
                'is_anonymous' => true,
            ])
            ->assertSessionHasErrors('is_anonymous');
    }

    public function test_only_the_tags_can_be_submitted(): void
    {
        $media = $this->mediaOwnedBySignedInUser(['title' => 'Kept', 'visibility' => Visibility::Public]);

        $this->patch("/m/{$media->hash_id}", ['tags' => ['cat', 'blue_eyes']])
            ->assertRedirect("/m/{$media->hash_id}");

        $media->refresh();

        $this->assertSame('Kept', $media->title);
        $this->assertSame(Visibility::Public, $media->visibility);
        $this->assertEqualsCanonicalizing(
            ['blue_eyes', 'cat'],
            $media->tags()->pluck('name')->all(),
        );
    }

    public function test_only_the_details_can_be_submitted_and_tags_survive(): void
    {
        $media = $this->mediaOwnedBySignedInUser();

        $this->patch("/m/{$media->hash_id}", ['tags' => ['cat']]);
        $this->patch("/m/{$media->hash_id}", ['title' => 'New title'])
            ->assertRedirect("/m/{$media->hash_id}");

        $media->refresh();

        $this->assertSame('New title', $media->title);
        $this->assertSame(['cat'], $media->tags()->pluck('name')->all());
    }

    public function test_the_edit_page_is_gone(): void
    {
        $media = $this->mediaOwnedBySignedInUser();

        $this->get("/m/{$media->hash_id}/edit")->assertNotFound();
    }
}
