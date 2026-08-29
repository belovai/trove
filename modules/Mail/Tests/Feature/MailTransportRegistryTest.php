<?php

declare(strict_types=1);

namespace Modules\Mail\Tests\Feature;

use App\Contracts\SettingRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mail\Support\MailTransportRegistry;
use Modules\Mail\Transports\LogTransport;
use Modules\Setting\Facades\Settings;
use Tests\TestCase;

final class MailTransportRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_log_transport_is_registered_and_always_configured(): void
    {
        $this->assertContains('log', MailTransportRegistry::keys());

        $transport = MailTransportRegistry::make('log');

        $this->assertInstanceOf(LogTransport::class, $transport);
        $this->assertTrue($transport->isConfigured());
        $this->assertSame(['transport' => 'log'], $transport->mailerConfig());
    }

    public function test_the_core_mail_settings_are_declared(): void
    {
        $registry = $this->app->make(SettingRegistry::class);

        foreach (['mail.enabled', 'mail.transport', 'mail.from_address', 'mail.from_name', 'mail.reply_to', 'mail.admin_address'] as $key) {
            $this->assertTrue($registry->has($key), "{$key} is not declared");
        }
    }

    public function test_mail_is_disabled_and_set_to_the_log_transport_by_default(): void
    {
        $this->assertFalse(Settings::get('mail.enabled'));
        $this->assertSame('log', Settings::get('mail.transport'));
    }
}
