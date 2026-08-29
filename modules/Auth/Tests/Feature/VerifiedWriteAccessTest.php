<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Setting\Facades\Settings;
use Modules\User\Models\User;
use Tests\TestCase;

final class VerifiedWriteAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_mode_does_not_block_an_unverified_user(): void
    {
        Settings::set('registration.verify', 'soft');
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => null]);

        $this->actingAs($user)->get('/upload')->assertOk();
    }

    public function test_required_mode_sends_an_unverified_user_to_the_notice(): void
    {
        Settings::set('registration.verify', 'required');
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => null]);

        $this->actingAs($user)->get('/upload')->assertRedirect('/verify-email');
    }

    public function test_required_mode_also_stops_a_user_without_an_address(): void
    {
        Settings::set('registration.verify', 'required');
        $user = User::factory()->create(['email' => null, 'email_verified_at' => null]);

        $this->actingAs($user)->get('/upload')->assertRedirect('/verify-email');
    }

    public function test_required_mode_lets_a_verified_user_through(): void
    {
        Settings::set('registration.verify', 'required');
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/upload')->assertOk();
    }

    public function test_browsing_and_account_settings_stay_reachable_in_required_mode(): void
    {
        Settings::set('registration.verify', 'required');
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => null]);

        $this->actingAs($user)->get('/posts')->assertOk();
        $this->actingAs($user)->get('/settings/account')->assertOk();
    }
}
