import { reactive } from 'vue';

export type ToastVariant = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
    id: number;
    variant: ToastVariant;
    message: string;
}

const DURATION_MS = 4000;

// Module-level state: the toast stack is mounted once, in AppLayout /
// GuestLayout, and every caller anywhere in the tree talks to it.
const toasts = reactive<Toast[]>([]);
let nextId = 1;

export function useToast() {
    const dismiss = (id: number): void => {
        const index = toasts.findIndex((toast) => toast.id === id);
        if (index !== -1) {
            toasts.splice(index, 1);
        }
    };

    const push = (variant: ToastVariant, message: string): void => {
        const id = nextId++;
        toasts.push({ id, variant, message });
        setTimeout(() => dismiss(id), DURATION_MS);
    };

    return { toasts, push, dismiss };
}
