<script setup lang="ts">
withDefaults(defineProps<{ id: string; invalid?: boolean; disabled?: boolean }>(), {
    invalid: false,
    disabled: false,
});

const model = defineModel<string | number | null>({ required: true });
</script>

<template>
    <select
        :id="id"
        v-model="model"
        :aria-invalid="invalid"
        :disabled="disabled"
        class="w-full rounded-md border bg-panel px-3 py-2 text-sm text-text disabled:opacity-60"
        :class="invalid ? 'border-danger' : 'border-divider'"
    >
        <slot />
    </select>
</template>
