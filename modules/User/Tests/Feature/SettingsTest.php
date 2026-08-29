<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_settings_lands_on_the_account_section(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/settings')
            ->assertRedirect('/settings/account');
    }

    public function test_the_old_account_url_redirects(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/account')
            ->assertRedirect('/settings/account');
    }

    public function test_the_account_section_renders(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/settings/account')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('settings/Account')
                ->where('current', 'account'));
    }

    public function test_the_profile_section_renders(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/settings/profile')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('settings/Profile'));
    }

    public function test_a_regular_user_sees_only_the_personal_sections(): void
    {
        $this->actingAs(User::factory()->create(['rank' => UserRank::Regular]))
            ->get('/settings/account')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sections.0.key', 'account')
                ->where('sections.1.key', 'profile')
                ->count('sections', 2));
    }

    public function test_an_administrator_sees_every_section(): void
    {
        $this->actingAs(User::factory()->create(['rank' => UserRank::Administrator]))
            ->get('/settings/account')
            ->assertInertia(fn (AssertableInertia $page) => $page->count('sections', 5));
    }
}
