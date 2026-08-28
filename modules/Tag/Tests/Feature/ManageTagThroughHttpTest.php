<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tag\Actions\CreateImplication;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;
use Modules\User\Models\User;
use Tests\TestCase;

final class ManageTagThroughHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_regular_user_is_refused(): void
    {
        $tag = Tag::factory()->create(['name' => 'cat']);

        $this->actingAs(User::factory()->create())
            ->patch('/tags/cat', ['name' => 'cats', 'category_id' => $tag->category_id])
            ->assertForbidden();
    }

    public function test_a_moderator_may_rename_and_recategorize(): void
    {
        $tag = Tag::factory()->create(['name' => 'cat']);
        $character = TagCategory::factory()->create(['name' => 'character']);

        $this->actingAs(User::factory()->moderator()->create())
            ->patch('/tags/cat', [
                'name' => 'cats',
                'category_id' => $character->id,
                'description' => 'Domestic cats.',
            ])
            ->assertRedirect();

        $tag->refresh();
        $this->assertSame('cats', $tag->name);
        $this->assertSame($character->id, $tag->category_id);
        $this->assertSame('Domestic cats.', $tag->description);
    }

    public function test_an_invalid_rename_is_a_validation_error(): void
    {
        Tag::factory()->create(['name' => 'cat']);

        $this->actingAs(User::factory()->moderator()->create())
            ->patch('/tags/cat', ['name' => 'artist:cat'])
            ->assertSessionHasErrors('name');
    }

    public function test_an_alias_can_be_added_and_removed(): void
    {
        $tag = Tag::factory()->create(['name' => 'cat']);
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($moderator)->post('/tags/cat/aliases', ['alias' => 'kitty'])->assertRedirect();
        $this->assertTrue($tag->aliases()->where('alias_name', 'kitty')->exists());

        $this->actingAs($moderator)->delete('/tags/cat/aliases/kitty')->assertRedirect();
        $this->assertFalse($tag->aliases()->where('alias_name', 'kitty')->exists());
    }

    public function test_an_implication_cycle_is_a_validation_error(): void
    {
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateImplication::class)->handle($calico, $cat);

        $this->actingAs(User::factory()->moderator()->create())
            ->post('/tags/cat/implications', ['implies' => 'calico'])
            ->assertSessionHasErrors('implies');
    }

    public function test_a_merge_redirects_to_the_target(): void
    {
        Tag::factory()->create(['name' => 'kitty']);
        Tag::factory()->create(['name' => 'cat']);

        $this->actingAs(User::factory()->moderator()->create())
            ->post('/tags/kitty/merge', ['into' => 'cat'])
            ->assertRedirect('/tags/cat');

        $this->assertNull(Tag::query()->where('name', 'kitty')->first());
    }

    public function test_a_tag_can_be_deleted(): void
    {
        Tag::factory()->create(['name' => 'cat']);

        $this->actingAs(User::factory()->moderator()->create())
            ->delete('/tags/cat')
            ->assertRedirect('/tags');

        $this->assertNull(Tag::query()->where('name', 'cat')->first());
    }
}
