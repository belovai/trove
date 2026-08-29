import type { Ref } from 'vue';

const FOCUSABLE =
    'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * Keeps Tab inside the overlay and restores focus to whatever opened it.
 */
export function useFocusTrap(element: Ref<HTMLElement | null>) {
    let previouslyFocused: HTMLElement | null = null;

    const onKeydown = (event: KeyboardEvent): void => {
        if (event.key !== 'Tab' || element.value === null) {
            return;
        }

        const focusable = Array.from(element.value.querySelectorAll<HTMLElement>(FOCUSABLE));

        if (focusable.length === 0) {
            event.preventDefault();

            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();

            return;
        }

        if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    };

    const activate = (): void => {
        previouslyFocused = document.activeElement as HTMLElement | null;
        document.addEventListener('keydown', onKeydown);
        element.value?.focus();
    };

    const deactivate = (): void => {
        document.removeEventListener('keydown', onKeydown);
        previouslyFocused?.focus();
    };

    return { activate, deactivate };
}
