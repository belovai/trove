<script setup lang="ts">
import AppHeader from '@/components/layout/AppHeader.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import ToastStack from '@/components/ui/ToastStack.vue';
import { useFlashToasts } from '@/composables/useFlashToasts';

useFlashToasts();
</script>

<template>
    <div class="min-h-screen bg-bg">
        <AppHeader />

        <main class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-6 md:px-6 md:py-8">
            <slot />
        </main>

        <!-- Mounted once: useConfirm() anywhere in the tree resolves here. -->
        <ConfirmDialog />
        <ToastStack />
    </div>
</template>
