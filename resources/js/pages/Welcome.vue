<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps<{
    laravelVersion: string;
    phpVersion: string;
}>();
</script>

<template>
    <Head title="Welcome" />

    <div class="flex flex-col gap-4">
        <h1 class="text-3xl font-semibold">Trove</h1>
        <p class="text-gray-600 dark:text-gray-400">Inertia + Vue 3 + TypeScript is running.</p>
        <p class="text-sm text-gray-500">Laravel {{ laravelVersion }} · PHP {{ phpVersion }}</p>
    </div>
</template>
