<script setup lang="ts">
defineProps<{ label: string; value: string; hint?: string }>();
</script>

<template>
    <div class="rounded-lg border border-divider bg-panel px-4 py-3 shadow-card">
        <p class="text-xs text-muted">{{ label }}</p>
        <p class="mt-1 text-xl font-semibold text-text">{{ value }}</p>
        <p v-if="hint" class="mt-1 text-xs text-muted">{{ hint }}</p>
    </div>
</template>
