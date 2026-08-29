<?php

declare(strict_types=1);

namespace Modules\Mail\Support;

use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Modules\Setting\Facades\Settings;

/**
 * The one place stored settings become Laravel's mail configuration.
 *
 * It runs per request and again before every queued job: a queue:work process
 * is long-lived and would otherwise keep the credentials that existed when it
 * booted, which is the most likely source of "I changed the password and it
 * still fails".
 */
final class MailConfigurator
{
    /** The mailer name this application always sends through. */
    public const MAILER = 'trove';

    public function apply(): void
    {
        config([
            'mail.default' => self::MAILER,
            'mail.mailers.'.self::MAILER => $this->mailerConfig(),
            'mail.from' => [
                'address' => $this->fromAddress(),
                'name' => $this->fromName(),
            ],
        ]);

        // A mailer resolved earlier in this process holds the previous
        // transport, so dropping the resolved instances is what makes a
        // settings change take effect.
        Mail::forgetMailers();

        $replyTo = trim((string) Settings::get('mail.reply_to'));

        if ($replyTo !== '') {
            Mail::alwaysReplyTo($replyTo);
        }
    }

    /** Whether an actual delivery attempt makes sense right now. */
    public function isDeliverable(): bool
    {
        if (Settings::get('mail.enabled') !== true) {
            return false;
        }

        try {
            return MailTransportRegistry::make((string) Settings::get('mail.transport'))->isConfigured();
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /** @return array<string, mixed> */
    private function mailerConfig(): array
    {
        if (Settings::get('mail.enabled') !== true) {
            // Not "log": disabled must mean nothing is written anywhere, and
            // the array transport keeps Mail::fake-style assertions working.
            return ['transport' => 'array'];
        }

        try {
            return MailTransportRegistry::make((string) Settings::get('mail.transport'))->mailerConfig();
        } catch (InvalidArgumentException) {
            // A transport that was removed from the codebase must not take the
            // site down on the next boot.
            return ['transport' => 'array'];
        }
    }

    private function fromAddress(): string
    {
        $address = trim((string) Settings::get('mail.from_address'));

        if ($address !== '') {
            return $address;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return 'noreply@'.(is_string($host) && $host !== '' ? $host : 'localhost');
    }

    private function fromName(): string
    {
        $name = trim((string) Settings::get('mail.from_name'));

        return $name !== '' ? $name : (string) Settings::get('app.name');
    }
}
