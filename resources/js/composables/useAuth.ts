import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useAuth() {
    const page = usePage();

    return {
        user: computed(() => page.props.auth.user),
        isAuthenticated: computed(() => page.props.auth.user !== null),
        can: (ability: string): boolean => page.props.auth.can[ability] === true,
    };
}
