<script setup lang="ts">
defineProps<{ id: string; label: string; error?: string; hint?: string }>();
</script>

<template>
    <div class="flex flex-col gap-1">
        <label :for="id" class="text-sm font-medium text-text">{{ label }}</label>
        <slot />
        <p v-if="hint && !error" class="text-xs text-muted">{{ hint }}</p>
        <p v-if="error" class="text-xs text-danger-strong">{{ error }}</p>
    </div>
</template>
