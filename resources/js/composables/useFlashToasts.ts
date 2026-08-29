import { onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

// Mount once per layout: turns the server's flash props into toasts on the
// initial page load and after every subsequent Inertia navigation.
export function useFlashToasts(): void {
    const page = usePage();
    const { push } = useToast();

    const flashCurrent = (): void => {
        const flash = page.props.flash;
        if (flash.error) {
            push('error', flash.error);
        }
        if (flash.success) {
            push('success', flash.success);
        }
    };

    flashCurrent();

    const stop = router.on('finish', flashCurrent);
    onUnmounted(stop);
}
