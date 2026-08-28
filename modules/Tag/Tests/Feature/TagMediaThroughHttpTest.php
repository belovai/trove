<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Modules\Tag\Actions\CreateImplication;
use Modules\Tag\Enums\TagSource;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;
use Modules\User\Models\User;
use Tests\TestCase;

final class TagMediaThroughHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_upload_carries_its_tags(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/upload', [
            'file' => UploadedFile::fake()->image('cat.jpg', 64, 64),
            'visibility' => 'public',
            'safety_rating' => 'safe',
            'tags' => ['cat', 'Long Cat'],
        ]);

        $response->assertCreated();

        $media = Media::query()->firstOrFail();
        $this->assertSame(['cat', 'long_cat'], $media->tags()->orderBy('name')->pluck('name')->all());
        $this->assertSame(2, $media->fresh()->tag_count);
    }

    public function test_an_edit_replaces_the_tag_set_and_recomputes_implications(): void
    {
        $user = User::factory()->moderator()->create();
        $media = Media::factory()->for($user, 'uploader')->create();
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateImplication::class)->handle($calico, $cat);

        $this->actingAs($user)->patch("/m/{$media->hash_id}", [
            'visibility' => $media->visibility->value,
            'safety_rating' => $media->safety_rating->value,
            'tags' => ['calico'],
        ])->assertRedirect();

        $this->assertSame(
            TagSource::Implied->value,
            $media->tags()->where('name', 'cat')->firstOrFail()->pivot->source,
        );
    }

    public function test_an_invalid_tag_name_is_a_validation_error(): void
    {
        $user = User::factory()->moderator()->create();
        $media = Media::factory()->for($user, 'uploader')->create();

        $this->actingAs($user)->patch("/m/{$media->hash_id}", [
            'visibility' => $media->visibility->value,
            'safety_rating' => $media->safety_rating->value,
            'tags' => ['-politics'],
        ])->assertSessionHasErrors('tags');
    }

    public function test_a_failed_request_creates_no_tags(): void
    {
        $user = User::factory()->moderator()->create();
        $media = Media::factory()->for($user, 'uploader')->create();

        // The tag names are fine; another rule fails. Nothing may be written.
        $this->actingAs($user)->patch("/m/{$media->hash_id}", [
            'visibility' => 'private',
            'safety_rating' => $media->safety_rating->value,
            'is_anonymous' => true,
            'tags' => ['brand_new_tag'],
        ])->assertSessionHasErrors('is_anonymous');

        $this->assertDatabaseMissing('tags', ['name' => 'brand_new_tag']);
    }

    public function test_an_unknown_category_prefix_is_a_validation_error(): void
    {
        $user = User::factory()->moderator()->create();
        $media = Media::factory()->for($user, 'uploader')->create();

        $this->actingAs($user)->patch("/m/{$media->hash_id}", [
            'visibility' => $media->visibility->value,
            'safety_rating' => $media->safety_rating->value,
            'tags' => ['nosuchcategory:foo'],
        ])->assertSessionHasErrors('tags');
    }

    public function test_a_prefix_on_an_existing_tag_does_not_recategorize_it(): void
    {
        $user = User::factory()->moderator()->create();
        $media = Media::factory()->for($user, 'uploader')->create();
        $character = TagCategory::factory()->create(['name' => 'character']);
        TagCategory::factory()->create(['name' => 'artist']);
        $tag = Tag::factory()->for($character, 'category')->create(['name' => 'john_wick']);

        $this->actingAs($user)->patch("/m/{$media->hash_id}", [
            'visibility' => $media->visibility->value,
            'safety_rating' => $media->safety_rating->value,
            'tags' => ['artist:john_wick'],
        ])->assertRedirect();

        $this->assertSame($character->id, $tag->fresh()->category_id);
    }
}
