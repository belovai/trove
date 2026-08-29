<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = withDefaults(
    defineProps<{
        href: string;
        variant?: 'primary' | 'secondary' | 'ghost';
        size?: 'sm' | 'md' | 'icon';
        block?: boolean;
    }>(),
    { variant: 'secondary', size: 'md', block: false },
);

const variants: Record<NonNullable<typeof props.variant>, string> = {
    primary: 'border-transparent bg-primary text-primary-fg hover:bg-primary-hover',
    secondary: 'border-divider bg-panel text-text hover:bg-surface',
    ghost: 'border-transparent bg-transparent text-muted hover:bg-surface hover:text-text',
};

const sizes: Record<NonNullable<typeof props.size>, string> = {
    sm: 'gap-1.5 px-2.5 py-1.5 text-xs',
    md: 'gap-2 px-3.5 py-2 text-sm',
    icon: 'p-1.5',
};

const classes = computed(() => [variants[props.variant], sizes[props.size], props.block ? 'w-full' : '']);
</script>

<template>
    <Link
        :href="href"
        class="inline-flex items-center justify-center rounded-md border font-medium transition-colors"
        :class="classes"
    >
        <slot name="icon" />
        <slot />
    </Link>
</template>
