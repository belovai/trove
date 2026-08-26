<script setup lang="ts">
withDefaults(
    defineProps<{
        variant?: 'success' | 'error';
    }>(),
    { variant: 'success' },
);
</script>

<template>
    <div
        class="rounded-md px-4 py-3 text-sm"
        :class="
            variant === 'error'
                ? 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200'
                : 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200'
        "
        role="status"
    >
        <slot />
    </div>
</template>
