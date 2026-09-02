import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatWith } from '@/support/datetime';

/**
 * The viewer's own date and time presentation, shared on every page as
 * `formats`. Timestamps arrive as ISO 8601 strings; nothing else on the client
 * turns one into text.
 */
export function useDateFormat() {
    const page = usePage();

    const formats = computed(() => page.props.formats);
    const locale = computed(() => page.props.locale);

    const formatDate = (iso: string | null, fallback = '—'): string =>
        iso === null ? fallback : formatWith(iso, formats.value.date, formats.value.timezone, locale.value);

    const formatTime = (iso: string | null, fallback = '—'): string =>
        iso === null ? fallback : formatWith(iso, formats.value.time, formats.value.timezone, locale.value);

    const formatDateTime = (iso: string | null, fallback = '—'): string =>
        iso === null
            ? fallback
            : formatWith(
                  iso,
                  `${formats.value.date} ${formats.value.time}`,
                  formats.value.timezone,
                  locale.value,
              );

    /**
     * Preview a pattern the viewer has not saved yet (the format pickers). The
     * timezone is passed in because the picker's own timezone select may hold
     * an unsaved value too.
     */
    const preview = (pattern: string, timezone?: string): string =>
        formatWith(new Date(), pattern, timezone ?? formats.value.timezone, locale.value);

    return { formats, formatDate, formatTime, formatDateTime, preview };
}
