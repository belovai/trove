<script setup lang="ts">
defineProps<{ title: string; description?: string }>();
</script>

<template>
    <section class="flex flex-col gap-3">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-text">{{ title }}</h2>
                <p v-if="description" class="mt-0.5 text-xs text-muted">{{ description }}</p>
            </div>
            <!-- The one place a "create" button lives. -->
            <div v-if="$slots.actions" class="shrink-0">
                <slot name="actions" />
            </div>
        </div>
        <slot />
    </section>
</template>
