<script setup lang="ts">
defineProps<{
    id: string;
    label: string;
    error?: string;
    hint?: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-1">
        <label :for="id" class="text-sm font-medium">{{ label }}</label>
        <slot />
        <p v-if="hint && !error" class="text-xs text-gray-500">{{ hint }}</p>
        <p v-if="error" class="text-xs text-red-600 dark:text-red-400">{{ error }}</p>
    </div>
</template>
