<?php

declare(strict_types=1);

namespace Modules\Mail\Tests\Feature;

use App\Contracts\SettingRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mail\Support\MailTransportRegistry;
use Modules\Setting\Facades\Settings;
use Modules\Setting\Models\Setting;
use Tests\TestCase;

final class SmtpTransportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_not_configured_without_a_host(): void
    {
        Settings::set('mail.smtp.host', '');

        $this->assertFalse(MailTransportRegistry::make('smtp')->isConfigured());
    }

    public function test_it_builds_a_laravel_smtp_configuration_from_the_stored_settings(): void
    {
        Settings::set('mail.smtp.host', 'mailpit');
        Settings::set('mail.smtp.port', 1025);
        Settings::set('mail.smtp.encryption', 'none');
        Settings::set('mail.smtp.username', 'trove');
        Settings::set('mail.smtp.password', 'secret');
        Settings::set('mail.smtp.timeout', 7);

        $config = MailTransportRegistry::make('smtp')->mailerConfig();

        $this->assertSame('smtp', $config['transport']);
        $this->assertSame('mailpit', $config['host']);
        $this->assertSame(1025, $config['port']);
        $this->assertNull($config['scheme']);
        $this->assertSame('trove', $config['username']);
        $this->assertSame('secret', $config['password']);
        $this->assertSame(7, $config['timeout']);
    }

    public function test_tls_and_ssl_map_to_the_smtp_schemes(): void
    {
        Settings::set('mail.smtp.host', 'smtp.example.com');

        Settings::set('mail.smtp.encryption', 'tls');
        $this->assertSame('smtp', MailTransportRegistry::make('smtp')->mailerConfig()['scheme']);

        Settings::set('mail.smtp.encryption', 'ssl');
        $this->assertSame('smtps', MailTransportRegistry::make('smtp')->mailerConfig()['scheme']);
    }

    public function test_the_password_is_declared_encrypted(): void
    {
        $definition = $this->app->make(SettingRegistry::class)->get('mail.smtp.password');

        $this->assertTrue($definition->isEncrypted);
    }

    public function test_the_stored_password_is_not_readable_as_plaintext_in_the_table(): void
    {
        Settings::set('mail.smtp.password', 'secret');

        $stored = Setting::query()->where('key', 'mail.smtp.password')->firstOrFail();

        $this->assertNotSame('"secret"', $stored->value);
        $this->assertTrue((bool) $stored->is_encrypted);
        $this->assertSame('secret', Settings::get('mail.smtp.password'));
    }
}
