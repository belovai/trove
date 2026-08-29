<?php

declare(strict_types=1);

namespace Modules\Mail\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mail\Support\MailConfigurator;
use Modules\Setting\Facades\Settings;
use Tests\TestCase;

final class MailConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_mail_selects_the_array_transport(): void
    {
        Settings::set('mail.enabled', false);
        Settings::set('mail.transport', 'smtp');
        Settings::set('mail.smtp.host', 'mailpit');

        $this->app->make(MailConfigurator::class)->apply();

        $this->assertSame('trove', config('mail.default'));
        $this->assertSame('array', config('mail.mailers.trove.transport'));
    }

    public function test_enabled_mail_uses_the_selected_transport(): void
    {
        Settings::set('mail.enabled', true);
        Settings::set('mail.transport', 'smtp');
        Settings::set('mail.smtp.host', 'mailpit');
        Settings::set('mail.smtp.port', 1025);

        $this->app->make(MailConfigurator::class)->apply();

        $this->assertSame('smtp', config('mail.mailers.trove.transport'));
        $this->assertSame('mailpit', config('mail.mailers.trove.host'));
        $this->assertSame(1025, config('mail.mailers.trove.port'));
    }

    public function test_a_settings_change_is_picked_up_by_a_later_apply(): void
    {
        Settings::set('mail.enabled', true);
        Settings::set('mail.transport', 'smtp');
        Settings::set('mail.smtp.host', 'first.example.com');
        $this->app->make(MailConfigurator::class)->apply();

        Settings::set('mail.smtp.host', 'second.example.com');
        $this->app->make(MailConfigurator::class)->apply();

        $this->assertSame('second.example.com', config('mail.mailers.trove.host'));
    }

    public function test_the_from_address_falls_back_to_the_application_host(): void
    {
        config(['app.url' => 'https://trove.test']);
        Settings::set('mail.from_address', '');
        Settings::set('mail.from_name', '');
        Settings::set('app.name', 'Trove');

        $this->app->make(MailConfigurator::class)->apply();

        $this->assertSame('noreply@trove.test', config('mail.from.address'));
        $this->assertSame('Trove', config('mail.from.name'));
    }

    public function test_it_is_deliverable_only_when_enabled_and_the_transport_is_configured(): void
    {
        $configurator = $this->app->make(MailConfigurator::class);

        Settings::set('mail.enabled', false);
        $this->assertFalse($configurator->isDeliverable());

        Settings::set('mail.enabled', true);
        Settings::set('mail.transport', 'smtp');
        Settings::set('mail.smtp.host', '');
        $this->assertFalse($configurator->isDeliverable());

        Settings::set('mail.smtp.host', 'mailpit');
        $this->assertTrue($configurator->isDeliverable());
    }
}
