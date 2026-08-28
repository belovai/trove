<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Media\Enums\SafetyRating;
use Modules\User\Models\User;
use Tests\TestCase;

final class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_open_the_account_page(): void
    {
        $this->get('/account')->assertRedirect('/login');
    }

    public function test_the_account_page_renders(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/account')
            ->assertOk();
    }

    public function test_a_user_can_set_a_display_name_and_locale(): void
    {
        $user = User::factory()->create(['display_name' => null, 'locale' => null]);

        $this->actingAs($user)
            ->patch('/account', ['display_name' => 'Arpad', 'locale' => 'hu'])
            ->assertRedirect('/account');

        $user->refresh();

        $this->assertSame('Arpad', $user->display_name);
        $this->assertSame('hu', $user->locale);
    }

    public function test_an_empty_display_name_clears_it(): void
    {
        $user = User::factory()->create(['display_name' => 'Arpad']);

        $this->actingAs($user)->patch('/account', ['display_name' => '', 'locale' => null]);

        $this->assertNull($user->fresh()->display_name);
    }

    public function test_a_user_can_set_their_default_safety_filter(): void
    {
        $user = User::factory()->create(['default_safety_filter' => 'safe']);

        $this->actingAs($user)
            ->patch('/account', ['display_name' => null, 'locale' => null, 'default_safety_filter' => 'unsafe'])
            ->assertRedirect('/account');

        $this->assertSame(SafetyRating::Unsafe, $user->fresh()->default_safety_filter);
    }

    public function test_an_unknown_default_safety_filter_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/account', ['display_name' => null, 'locale' => null, 'default_safety_filter' => 'spicy'])
            ->assertSessionHasErrors('default_safety_filter');
    }

    public function test_an_unsupported_locale_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/account', ['display_name' => null, 'locale' => 'de'])
            ->assertSessionHasErrors('locale');
    }

    public function test_a_user_can_change_their_password(): void
    {
        $user = User::factory()->create(['password' => 'password1']);

        $this->actingAs($user)->patch('/account/password', [
            'current_password' => 'password1',
            'password' => 'password2',
            'password_confirmation' => 'password2',
        ])->assertRedirect('/account');

        $this->assertTrue(Hash::check('password2', $user->fresh()->password));
    }

    public function test_a_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'password1']);

        $this->actingAs($user)->patch('/account/password', [
            'current_password' => 'wrong',
            'password' => 'password2',
            'password_confirmation' => 'password2',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password1', $user->fresh()->password));
    }

    public function test_a_user_can_delete_their_account(): void
    {
        $user = User::factory()->create(['password' => 'password1']);

        $this->actingAs($user)
            ->delete('/account', ['current_password' => 'password1'])
            ->assertRedirect('/');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertGuest();
    }

    public function test_deleting_requires_the_current_password(): void
    {
        $user = User::factory()->create(['password' => 'password1']);

        $this->actingAs($user)
            ->delete('/account', ['current_password' => 'wrong'])
            ->assertSessionHasErrors('current_password');

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }
}
