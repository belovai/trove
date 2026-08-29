<?php

declare(strict_types=1);

namespace Modules\Mail\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Setting\Facades\Settings;
use Modules\User\Enums\UserRank;
use Modules\User\Models\User;
use Tests\TestCase;

final class MailSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rank' => UserRank::Administrator]);
    }

    public function test_a_regular_user_cannot_see_the_page(): void
    {
        $this->actingAs(User::factory()->create(['rank' => UserRank::Regular]))
            ->get('/settings/mail')
            ->assertForbidden();
    }

    public function test_the_page_lists_the_transports_and_their_fields(): void
    {
        $response = $this->actingAs($this->admin())->get('/settings/mail')->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page->component('settings/Mail'));

        // The setting keys contain dots, which AssertableInertia reads as
        // nesting, so the props are asserted on directly.
        $props = $response->viewData('page')['props'];

        $this->assertSame(['log', 'smtp'], $props['transports']);
        $this->assertCount(6, $props['fields']['smtp']);
        $this->assertSame('log', $props['settings']['mail.transport']);
    }

    public function test_the_stored_password_is_never_sent_to_the_client(): void
    {
        Settings::set('mail.smtp.password', 'secret');

        $props = $this->actingAs($this->admin())
            ->get('/settings/mail')
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertTrue($props['secrets']['mail.smtp.password']);
        $this->assertSame('', $props['settings']['mail.smtp.password']);
        $this->assertStringNotContainsString('"secret"', json_encode($props));
    }

    public function test_an_administrator_can_save_the_settings(): void
    {
        $this->actingAs($this->admin())
            ->patch('/settings/mail', [
                'mail.enabled' => true,
                'mail.transport' => 'smtp',
                'mail.smtp.host' => 'mailpit',
                'mail.smtp.port' => 1025,
            ])
            ->assertRedirect();

        $this->assertTrue(Settings::get('mail.enabled'));
        $this->assertSame('smtp', Settings::get('mail.transport'));
        $this->assertSame('mailpit', Settings::get('mail.smtp.host'));
    }

    public function test_an_empty_secret_leaves_the_stored_value_unchanged(): void
    {
        Settings::set('mail.smtp.password', 'secret');

        $this->actingAs($this->admin())
            ->patch('/settings/mail', ['mail.smtp.password' => ''])
            ->assertRedirect();

        $this->assertSame('secret', Settings::get('mail.smtp.password'));
    }

    public function test_a_key_outside_the_mail_namespace_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->patch('/settings/mail', ['registration.mode' => 'closed'])
            ->assertSessionHasErrors('registration.mode');
    }

    public function test_an_invalid_value_is_rejected_before_it_is_written(): void
    {
        $this->actingAs($this->admin())
            ->patch('/settings/mail', ['mail.transport' => 'carrier-pigeon'])
            ->assertSessionHasErrors('mail.transport');

        $this->assertSame('log', Settings::get('mail.transport'));
    }
}
