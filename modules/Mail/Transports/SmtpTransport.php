<?php

declare(strict_types=1);

namespace Modules\Mail\Transports;

use Illuminate\Validation\Rule;
use Modules\Mail\Contracts\MailTransport;
use Modules\Setting\Facades\Settings;
use Modules\Setting\Support\SettingDefinition;

/**
 * Delivery through an SMTP server. Laravel 12 expresses TLS through the
 * "scheme" key ("smtp" with STARTTLS, "smtps" for implicit TLS), so the
 * operator-facing none/tls/ssl choice is mapped here rather than stored in
 * Laravel's own vocabulary.
 */
final class SmtpTransport implements MailTransport
{
    public static function key(): string
    {
        return 'smtp';
    }

    public static function label(): string
    {
        return 'mail::mail.transport_smtp';
    }

    public static function settings(): array
    {
        return [
            'mail.smtp.host' => SettingDefinition::string((string) env('MAIL_HOST', ''))
                ->rules(['nullable', 'string', 'max:255']),

            'mail.smtp.port' => SettingDefinition::int((int) env('MAIL_PORT', 587))
                ->rules(['required', 'integer', 'min:1', 'max:65535']),

            'mail.smtp.encryption' => SettingDefinition::string('tls')
                ->rules(['required', Rule::in(['none', 'tls', 'ssl'])]),

            'mail.smtp.username' => SettingDefinition::string((string) env('MAIL_USERNAME', ''))
                ->rules(['nullable', 'string', 'max:255']),

            'mail.smtp.password' => SettingDefinition::string('')
                ->encrypted()
                ->rules(['nullable', 'string', 'max:255']),

            'mail.smtp.timeout' => SettingDefinition::int(10)
                ->rules(['required', 'integer', 'min:1', 'max:120']),
        ];
    }

    public static function fields(): array
    {
        return [
            ['key' => 'mail.smtp.host', 'type' => 'text', 'label' => 'mail::mail.smtp_host'],
            ['key' => 'mail.smtp.port', 'type' => 'number', 'label' => 'mail::mail.smtp_port'],
            [
                'key' => 'mail.smtp.encryption',
                'type' => 'select',
                'label' => 'mail::mail.smtp_encryption',
                'options' => ['none', 'tls', 'ssl'],
            ],
            ['key' => 'mail.smtp.username', 'type' => 'text', 'label' => 'mail::mail.smtp_username'],
            ['key' => 'mail.smtp.password', 'type' => 'password', 'label' => 'mail::mail.smtp_password'],
            ['key' => 'mail.smtp.timeout', 'type' => 'number', 'label' => 'mail::mail.smtp_timeout'],
        ];
    }

    public function isConfigured(): bool
    {
        return trim((string) Settings::get('mail.smtp.host')) !== '';
    }

    public function mailerConfig(): array
    {
        $username = (string) Settings::get('mail.smtp.username');
        $password = (string) Settings::get('mail.smtp.password');

        return [
            'transport' => 'smtp',
            'scheme' => $this->scheme(),
            'host' => (string) Settings::get('mail.smtp.host'),
            'port' => (int) Settings::get('mail.smtp.port'),
            'username' => $username === '' ? null : $username,
            'password' => $password === '' ? null : $password,
            'timeout' => (int) Settings::get('mail.smtp.timeout'),
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
        ];
    }

    /**
     * null lets Symfony negotiate STARTTLS when the server offers it, which is
     * what "tls" means to an operator; "smtps" is implicit TLS on port 465.
     */
    private function scheme(): ?string
    {
        return match ((string) Settings::get('mail.smtp.encryption')) {
            'ssl' => 'smtps',
            'tls' => 'smtp',
            default => null,
        };
    }
}
