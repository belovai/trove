<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{ variant?: 'success' | 'error' | 'warning' | 'info' }>(), {
    variant: 'success',
});

const variants: Record<NonNullable<typeof props.variant>, string> = {
    success: 'bg-success-soft text-success',
    error: 'bg-danger-soft text-danger-strong',
    warning: 'bg-warning-soft text-warning',
    info: 'bg-info-soft text-info',
};

const classes = computed(() => variants[props.variant]);
</script>

<template>
    <div class="rounded-md px-4 py-3 text-sm" :class="classes" role="status">
        <slot />
    </div>
</template>
