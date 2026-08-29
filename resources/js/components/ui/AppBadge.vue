<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{ variant?: 'neutral' | 'accent' | 'success' | 'warning' | 'danger' }>(),
    { variant: 'neutral' },
);

const variants: Record<NonNullable<typeof props.variant>, string> = {
    neutral: 'bg-surface text-muted',
    accent: 'bg-info-soft text-info',
    success: 'bg-success-soft text-success',
    warning: 'bg-warning-soft text-warning',
    danger: 'bg-danger-soft text-danger-strong',
};

const classes = computed(() => variants[props.variant]);
</script>

<template>
    <span class="inline-flex items-center rounded-sm px-1.5 py-0.5 text-xs font-medium" :class="classes">
        <slot />
    </span>
</template>
