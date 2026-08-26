import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useTranslations() {
    const page = usePage();

    const t = (key: string, replacements: Record<string, string | number> = {}): string => {
        const line = page.props.translations[key];

        if (line === undefined) {
            return key;
        }

        return Object.entries(replacements).reduce(
            (carry, [name, value]) => carry.replaceAll(`:${name}`, String(value)),
            line,
        );
    };

    return {
        t,
        locale: computed(() => page.props.locale),
    };
}
