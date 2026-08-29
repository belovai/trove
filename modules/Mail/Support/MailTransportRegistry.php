<?php

declare(strict_types=1);

namespace Modules\Mail\Support;

use InvalidArgumentException;
use Modules\Mail\Contracts\MailTransport;
use Modules\Mail\Transports\LogTransport;
use Modules\Mail\Transports\SmtpTransport;
use Modules\Setting\Support\SettingDefinition;

/**
 * The list of delivery adapters. Static because Config/settings.php is a
 * plain require at boot and has no container to resolve from.
 */
final class MailTransportRegistry
{
    /** @var list<class-string<MailTransport>> */
    public const TRANSPORTS = [
        LogTransport::class,
        SmtpTransport::class,
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_map(fn (string $transport): string => $transport::key(), self::TRANSPORTS);
    }

    /** @return array<string, string> key => translation key */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::TRANSPORTS as $transport) {
            $labels[$transport::key()] = $transport::label();
        }

        return $labels;
    }

    /** @return array<string, SettingDefinition> */
    public static function definitions(): array
    {
        $definitions = [];

        foreach (self::TRANSPORTS as $transport) {
            $definitions += $transport::settings();
        }

        return $definitions;
    }

    /** @return array<string, list<array{key: string, type: string, label: string, options?: list<string>}>> */
    public static function fields(): array
    {
        $fields = [];

        foreach (self::TRANSPORTS as $transport) {
            $fields[$transport::key()] = $transport::fields();
        }

        return $fields;
    }

    public static function make(string $key): MailTransport
    {
        foreach (self::TRANSPORTS as $transport) {
            if ($transport::key() === $key) {
                return app($transport);
            }
        }

        throw new InvalidArgumentException("Unknown mail transport [{$key}].");
    }
}
