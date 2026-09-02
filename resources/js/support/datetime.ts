/**
 * Rendering a UTC timestamp the way the viewer asked for it.
 *
 * The server stores and sends timestamps in UTC (ISO 8601) and declares the
 * viewer's timezone plus two PHP `date()` patterns — see
 * `App\Support\DateTimeFormats`. This module is the only place those patterns
 * are interpreted on the client, so every screen renders a date the same way.
 *
 * Supported tokens (the subset the DateFormat / TimeFormat enums use, plus the
 * obvious neighbours): Y y m n d j D l M F H G h g i s A a. A backslash escapes
 * the next character. Anything else is copied through verbatim.
 */

export interface ViewerFormats {
    timezone: string;
    date: string;
    time: string;
}

const formatterCache = new Map<string, Intl.DateTimeFormat>();

function formatter(key: string, build: () => Intl.DateTimeFormat): Intl.DateTimeFormat {
    let cached = formatterCache.get(key);

    if (cached === undefined) {
        cached = build();
        formatterCache.set(key, cached);
    }

    return cached;
}

/**
 * The numeric fields, read in the target timezone. `en-US` is deliberate: it
 * keeps the digits latin whatever the interface language is. Names are looked
 * up separately, in the interface language.
 */
function numericParts(date: Date, timeZone: string): Record<string, string> {
    const parts = formatter(`n:${timeZone}`, () =>
        new Intl.DateTimeFormat('en-US', {
            timeZone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        }),
    ).formatToParts(date);

    const out: Record<string, string> = {};

    for (const part of parts) {
        out[part.type] = part.value;
    }

    return out;
}

function namedPart(
    date: Date,
    timeZone: string,
    locale: string,
    type: 'month' | 'weekday',
    width: 'short' | 'long',
): string {
    const parts = formatter(`${type}:${width}:${locale}:${timeZone}`, () =>
        new Intl.DateTimeFormat(locale, { timeZone, [type]: width }),
    ).formatToParts(date);

    return parts.find((part) => part.type === type)?.value ?? '';
}

function unpad(value: string): string {
    return String(Number(value));
}

/**
 * Format one date with an explicit pattern. Used directly only where the
 * pattern is not the viewer's own — the format picker's live preview.
 */
export function formatWith(value: Date | string, pattern: string, timezone: string, locale: string): string {
    const date = value instanceof Date ? value : new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const parts = numericParts(date, timezone);
    // Some engines report midnight as hour 24 with hour12: false.
    const hour24 = parts.hour === '24' ? 0 : Number(parts.hour);
    const hour12 = hour24 % 12 === 0 ? 12 : hour24 % 12;
    const meridiem = hour24 < 12 ? 'AM' : 'PM';

    let out = '';

    for (let index = 0; index < pattern.length; index += 1) {
        const token = pattern[index];

        if (token === '\\') {
            index += 1;
            out += pattern[index] ?? '';
            continue;
        }

        switch (token) {
            case 'Y':
                out += parts.year;
                break;
            case 'y':
                out += parts.year.slice(-2);
                break;
            case 'm':
                out += parts.month;
                break;
            case 'n':
                out += unpad(parts.month);
                break;
            case 'd':
                out += parts.day;
                break;
            case 'j':
                out += unpad(parts.day);
                break;
            case 'M':
                out += namedPart(date, timezone, locale, 'month', 'short');
                break;
            case 'F':
                out += namedPart(date, timezone, locale, 'month', 'long');
                break;
            case 'D':
                out += namedPart(date, timezone, locale, 'weekday', 'short');
                break;
            case 'l':
                out += namedPart(date, timezone, locale, 'weekday', 'long');
                break;
            case 'H':
                out += String(hour24).padStart(2, '0');
                break;
            case 'G':
                out += String(hour24);
                break;
            case 'h':
                out += String(hour12).padStart(2, '0');
                break;
            case 'g':
                out += String(hour12);
                break;
            case 'i':
                out += parts.minute;
                break;
            case 's':
                out += parts.second;
                break;
            case 'A':
                out += meridiem;
                break;
            case 'a':
                out += meridiem.toLowerCase();
                break;
            default:
                out += token;
        }
    }

    return out;
}
