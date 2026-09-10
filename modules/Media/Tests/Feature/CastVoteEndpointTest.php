<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Media;
use Modules\Setting\Facades\Settings;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class CastVoteEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function voter(): User
    {
        return User::factory()->create(['rank' => UserRank::Regular]);
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $media = Media::factory()->create();

        $this->post("/m/{$media->hash_id}/vote", ['value' => 1])
            ->assertRedirect('/login');

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_a_regular_user_can_vote_up(): void
    {
        $media = Media::factory()->create();

        $this->actingAs($this->voter())
            ->post("/m/{$media->hash_id}/vote", ['value' => 1])
            ->assertRedirect();

        $this->assertSame(1, $media->fresh()->score);
    }

    public function test_sending_zero_withdraws_the_vote(): void
    {
        $media = Media::factory()->create();
        $voter = $this->voter();

        $this->actingAs($voter)->post("/m/{$media->hash_id}/vote", ['value' => 1]);
        $this->actingAs($voter)->post("/m/{$media->hash_id}/vote", ['value' => 0]);

        $this->assertSame(0, $media->fresh()->score);
        $this->assertDatabaseCount('votes', 0);
    }

    public function test_switching_direction_moves_the_score_by_two(): void
    {
        $media = Media::factory()->create();
        $voter = $this->voter();

        $this->actingAs($voter)->post("/m/{$media->hash_id}/vote", ['value' => 1]);
        $this->actingAs($voter)->post("/m/{$media->hash_id}/vote", ['value' => -1]);

        $this->assertSame(-1, $media->fresh()->score);
        $this->assertDatabaseCount('votes', 1);
    }

    public function test_a_restricted_user_is_refused(): void
    {
        $media = Media::factory()->create();

        $this->actingAs(User::factory()->create(['rank' => UserRank::Restricted]))
            ->post("/m/{$media->hash_id}/vote", ['value' => 1])
            ->assertForbidden();
    }

    public function test_voting_on_your_own_upload_is_refused(): void
    {
        $voter = $this->voter();
        $media = Media::factory()->for($voter, 'uploader')->create();

        $this->actingAs($voter)
            ->post("/m/{$media->hash_id}/vote", ['value' => 1])
            ->assertForbidden();
    }

    public function test_an_invisible_item_is_not_found(): void
    {
        $media = Media::factory()->private()->create();

        $this->actingAs($this->voter())
            ->post("/m/{$media->hash_id}/vote", ['value' => 1])
            ->assertNotFound();
    }

    public function test_an_out_of_range_value_is_rejected(): void
    {
        $media = Media::factory()->create();

        $this->actingAs($this->voter())
            ->post("/m/{$media->hash_id}/vote", ['value' => 5])
            ->assertSessionHasErrors('value');
    }

    public function test_an_unverified_email_does_not_block_voting(): void
    {
        Settings::set('registration.verify', 'required');

        $media = Media::factory()->create();
        $voter = User::factory()->create(['rank' => UserRank::Regular, 'email_verified_at' => null]);

        $this->actingAs($voter)
            ->post("/m/{$media->hash_id}/vote", ['value' => 1])
            ->assertRedirect();

        $this->assertSame(1, $media->fresh()->score);
    }
}
