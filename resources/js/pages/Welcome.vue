<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    laravelVersion: string;
    phpVersion: string;
}>();
</script>

<template>
    <Head title="Welcome" />

    <main class="mx-auto flex min-h-screen max-w-2xl flex-col justify-center gap-4 px-6">
        <h1 class="text-3xl font-semibold">Trove</h1>
        <p class="text-gray-600 dark:text-gray-400">
            Inertia + Vue 3 + TypeScript is running.
        </p>
        <p class="text-sm text-gray-500">
            Laravel {{ laravelVersion }} · PHP {{ phpVersion }}
        </p>
    </main>
</template>
