<script setup lang="ts">
withDefaults(
    defineProps<{
        type?: 'button' | 'submit';
        disabled?: boolean;
    }>(),
    { type: 'button', disabled: false },
);
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 disabled:opacity-50 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-300"
    >
        <slot />
    </button>
</template>
