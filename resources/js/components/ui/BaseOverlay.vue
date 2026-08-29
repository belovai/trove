<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useScrollLock } from '@/composables/useScrollLock';
import { useFocusTrap } from '@/composables/useFocusTrap';

defineProps<{ titleId: string; variant: 'center' | 'side' }>();

const emit = defineEmits<{ close: [] }>();

const panel = ref<HTMLElement | null>(null);
const { lock, unlock } = useScrollLock();
const { activate, deactivate } = useFocusTrap(panel);

const onKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Escape') {
        emit('close');
    }
};

onMounted(() => {
    lock();
    activate();
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    deactivate();
    unlock();
});
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-40 bg-black/45" @click="emit('close')" />

        <!-- One overlay, two shapes: below md both are full-screen, and only
             the md: classes differ. -->
        <div
            ref="panel"
            class="fixed inset-0 z-50 flex flex-col bg-panel md:inset-auto"
            :class="
                variant === 'side'
                    ? 'md:top-0 md:right-0 md:h-full md:w-[28rem] md:shadow-modal'
                    : 'md:top-1/2 md:left-1/2 md:max-h-[85vh] md:w-full md:max-w-lg md:-translate-x-1/2 md:-translate-y-1/2 md:rounded-xl md:shadow-modal'
            "
            role="dialog"
            aria-modal="true"
            :aria-labelledby="titleId"
            tabindex="-1"
        >
            <slot />
        </div>
    </Teleport>
</template>
