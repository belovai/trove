<script setup lang="ts">
defineProps<{ label: string; description?: string }>();
</script>

<template>
    <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
        <div class="min-w-0">
            <p class="text-sm font-medium text-text">{{ label }}</p>
            <p v-if="description" class="mt-0.5 text-xs text-muted">{{ description }}</p>
        </div>
        <div v-if="$slots.default" class="shrink-0 sm:text-right">
            <slot />
        </div>
    </div>
</template>
