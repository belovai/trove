<script setup lang="ts">
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { useToast, type ToastVariant } from '@/composables/useToast';
import { useTranslations } from '@/composables/useTranslations';

const { toasts, dismiss } = useToast();
const { t } = useTranslations();

const variants: Record<ToastVariant, string> = {
    success: 'bg-success-soft text-success',
    error: 'bg-danger-soft text-danger-strong',
    warning: 'bg-warning-soft text-warning',
    info: 'bg-info-soft text-info',
};
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed inset-x-0 top-4 z-80 flex flex-col items-center gap-2 px-4">
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex w-full max-w-sm items-start gap-2 rounded-lg px-3 py-2 text-sm shadow-pop"
                    :class="variants[toast.variant]"
                    role="status"
                >
                    <p class="min-w-0 flex-1 break-words">{{ toast.message }}</p>
                    <button
                        type="button"
                        class="shrink-0 opacity-70 hover:opacity-100"
                        :aria-label="t('user::ui.close')"
                        @click="dismiss(toast.id)"
                    >
                        <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.15s ease-out;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-0.5rem);
}
.toast-leave-active {
    position: absolute;
}
</style>
