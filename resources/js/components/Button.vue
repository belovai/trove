<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        type?: 'button' | 'submit';
        disabled?: boolean;
        variant?: 'primary' | 'secondary' | 'warning';
    }>(),
    { type: 'button', disabled: false, variant: 'primary' },
);

// Colour carries the meaning: the warning variant belongs to a card that is
// already yellow, the secondary one to the choice you take by walking away.
const variants: Record<NonNullable<typeof props.variant>, string> = {
    primary:
        'bg-gray-900 text-white hover:bg-gray-700 focus-visible:ring-gray-500 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-300',
    secondary:
        'border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 focus-visible:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800',
    warning: 'bg-yellow-500 text-white hover:bg-yellow-600 focus-visible:ring-yellow-400',
};

const classes = computed(() => variants[props.variant]);
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 disabled:opacity-50"
        :class="classes"
    >
        <slot />
    </button>
</template>
