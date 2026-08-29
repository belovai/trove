<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        type?: 'button' | 'submit';
        variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
        size?: 'sm' | 'md' | 'icon';
        block?: boolean;
        disabled?: boolean;
        loading?: boolean;
    }>(),
    { type: 'button', variant: 'primary', size: 'md', block: false, disabled: false, loading: false },
);

// The variant carries the meaning: `danger` is the confirmation itself, never
// the trigger that opens it — a trigger in a row is a ghost icon button.
const variants: Record<NonNullable<typeof props.variant>, string> = {
    primary: 'border-transparent bg-primary text-primary-fg hover:bg-primary-hover',
    secondary: 'border-divider bg-panel text-text hover:bg-surface',
    ghost: 'border-transparent bg-transparent text-muted hover:bg-surface hover:text-text',
    danger: 'border-transparent bg-danger text-white hover:bg-danger-strong',
};

const sizes: Record<NonNullable<typeof props.size>, string> = {
    sm: 'gap-1.5 px-2.5 py-1.5 text-xs',
    md: 'gap-2 px-3.5 py-2 text-sm',
    icon: 'p-1.5',
};

const classes = computed(() => [
    variants[props.variant],
    sizes[props.size],
    props.block ? 'w-full' : '',
]);
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :aria-busy="loading"
        class="inline-flex cursor-pointer items-center justify-center rounded-md border font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-60"
        :class="classes"
    >
        <slot name="icon" />
        <slot />
    </button>
</template>
