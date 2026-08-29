import { readonly, ref } from 'vue';

export interface ConfirmOptions {
    message: string;
    title?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'primary' | 'danger';
}

interface PendingConfirm extends ConfirmOptions {
    settle: (accepted: boolean) => void;
}

// Module-level state: the dialog is mounted once, in AppLayout, and every
// caller anywhere in the tree talks to that one instance.
const pending = ref<PendingConfirm | null>(null);

export function useConfirm() {
    const confirm = (options: ConfirmOptions): Promise<boolean> =>
        new Promise<boolean>((settle) => {
            pending.value = { ...options, settle };
        });

    const resolve = (accepted: boolean): void => {
        pending.value?.settle(accepted);
        pending.value = null;
    };

    return { confirm, state: readonly(pending), resolve };
}
