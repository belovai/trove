<?php

declare(strict_types=1);

namespace Modules\Mail\Contracts;

use Modules\Setting\Support\SettingDefinition;

/**
 * One way of delivering mail. An adapter declares the settings it needs and
 * turns them into the configuration array Laravel's mail manager expects, so
 * adding a provider later is one class and a registry entry.
 */
interface MailTransport
{
    /** Stable identifier; also the stored value of mail.transport. */
    public static function key(): string;

    /** Translation key for the label in the transport select. */
    public static function label(): string;

    /**
     * The setting keys this transport owns. Every key must start with
     * "mail." — the namespace belongs to the declaring module.
     *
     * @return array<string, SettingDefinition>
     */
    public static function settings(): array;

    /**
     * Descriptors the admin form renders, in display order.
     *
     * @return list<array{key: string, type: string, label: string, options?: list<string>}>
     */
    public static function fields(): array;

    /** Whether the stored values are complete enough to attempt a send. */
    public function isConfigured(): bool;

    /** @return array<string, mixed> */
    public function mailerConfig(): array;
}
