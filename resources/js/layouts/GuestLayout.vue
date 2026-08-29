<script setup lang="ts">
import ToastStack from '@/components/ui/ToastStack.vue';
import { useFlashToasts } from '@/composables/useFlashToasts';

useFlashToasts();
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center gap-6 bg-bg px-6">
        <h1 class="text-2xl font-semibold text-text">Trove</h1>

        <div class="w-full max-w-sm rounded-lg border border-divider bg-panel p-6 shadow-card">
            <slot />
        </div>

        <ToastStack />
    </div>
</template>
