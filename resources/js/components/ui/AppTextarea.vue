<script setup lang="ts">
withDefaults(
    defineProps<{ id: string; rows?: number; invalid?: boolean; disabled?: boolean }>(),
    { rows: 4, invalid: false, disabled: false },
);

const model = defineModel<string>({ required: true });
</script>

<template>
    <textarea
        :id="id"
        v-model="model"
        :rows="rows"
        :aria-invalid="invalid"
        :disabled="disabled"
        class="w-full rounded-md border bg-panel px-3 py-2 text-sm text-text placeholder:text-muted disabled:opacity-60"
        :class="invalid ? 'border-danger' : 'border-divider'"
    />
</template>
