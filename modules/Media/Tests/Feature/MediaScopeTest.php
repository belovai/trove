<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Media;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class MediaScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_attributed_to_hides_anonymous_items_from_a_stranger(): void
    {
        $uploader = User::factory()->create();
        $named = Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => false]);
        Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => true]);

        $stranger = User::factory()->create(['rank' => UserRank::Regular]);

        $ids = Media::query()->attributedTo($uploader, $stranger)->pluck('id')->all();

        $this->assertSame([$named->id], $ids);
    }

    public function test_attributed_to_hides_anonymous_items_from_a_guest(): void
    {
        $uploader = User::factory()->create();
        $named = Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => false]);
        Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => true]);

        $ids = Media::query()->attributedTo($uploader, null)->pluck('id')->all();

        $this->assertSame([$named->id], $ids);
    }

    public function test_attributed_to_shows_anonymous_items_to_the_uploader(): void
    {
        $uploader = User::factory()->create();
        Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => false]);
        Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => true]);

        $this->assertCount(2, Media::query()->attributedTo($uploader, $uploader)->get());
    }

    public function test_attributed_to_shows_anonymous_items_to_a_moderator(): void
    {
        $uploader = User::factory()->create();
        Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => false]);
        Media::factory()->create(['user_id' => $uploader->id, 'is_anonymous' => true]);

        $moderator = User::factory()->create(['rank' => UserRank::Moderator]);

        $this->assertCount(2, Media::query()->attributedTo($uploader, $moderator)->get());
    }

    public function test_attributed_to_excludes_other_uploaders(): void
    {
        $uploader = User::factory()->create();
        $mine = Media::factory()->create(['user_id' => $uploader->id]);
        Media::factory()->create();

        $ids = Media::query()->attributedTo($uploader, $uploader)->pluck('id')->all();

        $this->assertSame([$mine->id], $ids);
    }
}
