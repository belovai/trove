<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Enums\RegistrationMode;
use Modules\Setting\Facades\Settings;
use Tests\TestCase;

final class RegistrationModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_closed_mode_hides_the_registration_page(): void
    {
        Settings::set('registration.mode', RegistrationMode::Closed);

        $this->get('/register')->assertNotFound();
    }

    public function test_closed_mode_rejects_a_registration_attempt(): void
    {
        Settings::set('registration.mode', RegistrationMode::Closed);

        $this->post('/register', [
            'username' => 'rp',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['username' => 'rp']);
    }

    public function test_the_named_route_resolves_even_in_closed_mode(): void
    {
        Settings::set('registration.mode', RegistrationMode::Closed);

        // Templates must not break when registration is closed.
        $this->assertSame(url('/register'), route('register'));
    }

    public function test_the_login_page_reports_whether_registration_is_open(): void
    {
        Settings::set('registration.mode', RegistrationMode::Closed);
        $this->get('/login')->assertOk()->assertInertia(
            fn ($page) => $page->where('canRegister', false),
        );

        Settings::set('registration.mode', RegistrationMode::Open);
        $this->get('/login')->assertOk()->assertInertia(
            fn ($page) => $page->where('canRegister', true),
        );
    }

    public function test_open_mode_still_registers(): void
    {
        Settings::set('registration.mode', RegistrationMode::Open);

        $this->post('/register', [
            'username' => 'rp',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertRedirect('/');

        $this->assertDatabaseHas('users', ['username' => 'rp']);
    }
}
