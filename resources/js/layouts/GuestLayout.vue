<script setup lang="ts">
import Alert from '@/components/Alert.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center gap-6 bg-gray-50 px-6 dark:bg-gray-950">
        <h1 class="text-2xl font-semibold">Trove</h1>

        <div class="w-full max-w-sm rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <Alert v-if="page.props.flash.error" variant="error" class="mb-4">
                {{ page.props.flash.error }}
            </Alert>
            <Alert v-if="page.props.flash.success" class="mb-4">
                {{ page.props.flash.success }}
            </Alert>

            <slot />
        </div>
    </div>
</template>
