<?php

declare(strict_types=1);

namespace Modules\Mail\Transports;

use Modules\Mail\Contracts\MailTransport;

/**
 * Writes the message to the log channel. It needs no configuration, which is
 * why it is the default: a fresh installation is never in a state where no
 * transport can be selected.
 */
final class LogTransport implements MailTransport
{
    public static function key(): string
    {
        return 'log';
    }

    public static function label(): string
    {
        return 'mail::mail.transport_log';
    }

    public static function settings(): array
    {
        return [];
    }

    public static function fields(): array
    {
        return [];
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function mailerConfig(): array
    {
        return ['transport' => 'log'];
    }
}
