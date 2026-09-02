<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeZone;
use Modules\Setting\Facades\Settings;
use Modules\User\Enums\DateFormat;
use Modules\User\Enums\TimeFormat;
use Modules\User\Models\User;

/**
 * Resolves how timestamps are presented for one viewer: their own preference
 * when they have set one, the system default otherwise.
 *
 * The values cross the wire as PHP date() patterns and the client formatter
 * understands the same tokens, so a timestamp is sent as ISO 8601 once and
 * rendered in exactly one place.
 */
final class DateTimeFormats
{
    /**
     * @return array{timezone: string, date: string, time: string}
     */
    public static function for(?User $user): array
    {
        $timezone = $user === null ? null : $user->timezone;
        $date = $user === null ? null : $user->date_format;
        $time = $user === null ? null : $user->time_format;

        $systemDate = Settings::get('app.date_format');
        $systemTime = Settings::get('app.time_format');

        return [
            'timezone' => $timezone ?? (string) Settings::get('app.timezone'),
            'date' => ($date ?? ($systemDate instanceof DateFormat ? $systemDate : DateFormat::Iso))->value,
            'time' => ($time ?? ($systemTime instanceof TimeFormat ? $systemTime : TimeFormat::TwentyFour))->value,
        ];
    }

    /**
     * Every selectable timezone, with its current UTC offset, so the client
     * renders a label without re-deriving offsets of its own.
     *
     * @return list<array{value: string, offset: string}>
     */
    public static function timezones(): array
    {
        $timezones = [];

        foreach (DateTimeZone::listIdentifiers() as $identifier) {
            $offset = (new DateTimeZone($identifier))->getOffset(now());
            $sign = $offset < 0 ? '-' : '+';
            $offset = abs($offset);

            $timezones[] = [
                'value' => $identifier,
                'offset' => sprintf('UTC%s%02d:%02d', $sign, intdiv($offset, 3600), intdiv($offset % 3600, 60)),
            ];
        }

        return $timezones;
    }
}
