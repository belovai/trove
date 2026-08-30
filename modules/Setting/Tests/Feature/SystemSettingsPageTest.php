<?php

declare(strict_types=1);

namespace Modules\Setting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Enums\RegistrationMode;
use Modules\Media\Enums\Visibility;
use Modules\Setting\Facades\Settings;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class SystemSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::factory()->create(['rank' => UserRank::Administrator]);
    }

    public function test_a_regular_user_may_not_see_the_page(): void
    {
        $this->actingAs(User::factory()->create(['rank' => UserRank::Regular]))
            ->get('/settings/system')
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get('/settings/system')->assertRedirect('/login');
    }

    public function test_an_administrator_sees_the_current_values(): void
    {
        Settings::set('app.name', 'My Gallery');

        $this->actingAs($this->administrator())
            ->get('/settings/system')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/System')
                ->where('settings', fn ($settings) => $settings['app.name'] === 'My Gallery'
                    && $settings['registration.mode'] === 'open'));
    }

    public function test_an_administrator_can_save_a_value(): void
    {
        $this->actingAs($this->administrator())
            ->patch('/settings/system', ['app.name' => 'My Gallery'])
            ->assertRedirect();

        $this->assertSame('My Gallery', Settings::get('app.name'));
    }

    public function test_a_partial_update_leaves_other_keys_untouched(): void
    {
        Settings::set('registration.mode', RegistrationMode::Closed);

        $this->actingAs($this->administrator())
            ->patch('/settings/system', ['app.name' => 'My Gallery']);

        $this->assertSame(RegistrationMode::Closed, Settings::get('registration.mode'));
    }

    public function test_a_key_outside_the_page_is_rejected(): void
    {
        $this->actingAs($this->administrator())
            ->patch('/settings/system', ['media.disk' => 's3'])
            ->assertSessionHasErrors();
    }

    public function test_an_invalid_value_is_rejected(): void
    {
        $this->actingAs($this->administrator())
            ->patch('/settings/system', ['app.name' => ''])
            ->assertSessionHasErrors();

        $this->assertSame('Trove', Settings::get('app.name'));
    }

    public function test_an_unrecognized_enum_value_is_rejected_and_nothing_is_stored(): void
    {
        $this->actingAs($this->administrator())
            ->patch('/settings/system', ['registration.mode' => 'banana'])
            ->assertSessionHasErrors('registration.mode');

        $this->assertSame(RegistrationMode::Open, Settings::get('registration.mode'));
    }

    public function test_a_non_boolean_approval_value_is_rejected(): void
    {
        $this->actingAs($this->administrator())
            ->patch('/settings/system', ['registration.approval' => 'not-a-boolean'])
            ->assertSessionHasErrors('registration.approval');

        $this->assertFalse(Settings::get('registration.approval'));
    }

    public function test_an_invalid_value_does_not_leave_an_earlier_key_in_the_same_request_written(): void
    {
        Settings::set('registration.mode', RegistrationMode::Open);

        $this->actingAs($this->administrator())
            ->patch('/settings/system', [
                'registration.mode' => RegistrationMode::Closed->value,
                'app.name' => '',
            ])
            ->assertSessionHasErrors();

        $this->assertSame(RegistrationMode::Open, Settings::get('registration.mode'));
        $this->assertSame('Trove', Settings::get('app.name'));
    }

    public function test_the_default_media_visibility_is_public_after_install(): void
    {
        $this->assertSame(Visibility::Public, Settings::get('media.default_visibility'));
    }

    public function test_an_administrator_can_change_the_default_media_visibility(): void
    {
        $this->actingAs($this->administrator())
            ->patch('/settings/system', ['media.default_visibility' => 'unlisted'])
            ->assertRedirect();

        $this->assertSame(Visibility::Unlisted, Settings::get('media.default_visibility'));
    }

    public function test_an_administrator_can_save_the_blocked_names_list(): void
    {
        $this->actingAs($this->administrator())
            ->patch('/settings/system', ['registration.blocked_names' => ['Anonymous', 'Support']])
            ->assertRedirect();

        $this->assertSame(['Anonymous', 'Support'], Settings::get('registration.blocked_names'));
    }

    public function test_a_regular_user_may_not_save(): void
    {
        $this->actingAs(User::factory()->create(['rank' => UserRank::Regular]))
            ->patch('/settings/system', ['app.name' => 'Nope'])
            ->assertForbidden();
    }
}
