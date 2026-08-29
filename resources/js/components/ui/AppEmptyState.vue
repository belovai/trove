<script setup lang="ts">
defineProps<{ title: string; description?: string }>();
</script>

<template>
    <div class="flex flex-col items-center gap-2 px-5 py-10 text-center">
        <p class="text-sm font-medium text-text">{{ title }}</p>
        <p v-if="description" class="max-w-sm text-xs text-muted">{{ description }}</p>
        <div v-if="$slots.action" class="mt-2">
            <slot name="action" />
        </div>
    </div>
</template>
