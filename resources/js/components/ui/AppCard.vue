<script setup lang="ts">
withDefaults(defineProps<{ title?: string; description?: string; padded?: boolean }>(), { padded: true });
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-divider bg-panel shadow-card">
        <header v-if="title || $slots.header" class="border-b border-divider bg-surface px-5 py-3">
            <slot name="header">
                <h3 class="text-sm font-semibold text-text">{{ title }}</h3>
                <p v-if="description" class="mt-0.5 text-xs text-muted">{{ description }}</p>
            </slot>
        </header>

        <div :class="padded ? 'px-5 py-4' : ''">
            <slot />
        </div>

        <footer v-if="$slots.footer" class="border-t border-divider bg-surface px-5 py-3">
            <slot name="footer" />
        </footer>
    </div>
</template>
